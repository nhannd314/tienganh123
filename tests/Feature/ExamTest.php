<?php

use App\Enums\ExamGroupType;
use App\Jobs\FetchWordPronunciation;
use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamGroup;
use App\Models\ExamQuestion;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(fn () => Queue::fake([FetchWordPronunciation::class]));

/**
 * Start the exam as a student, so the attempt is kept in the session.
 */
function startExam(Exam $exam, array $data = []): ExamAttempt
{
    test()->post(route('exams.start', $exam), [...['student_name' => 'Nguyễn An', 'class_name' => '3A'], ...$data])
        ->assertRedirect(route('exams.take', $exam));

    return ExamAttempt::query()->latest('id')->firstOrFail();
}

/**
 * @return array<string, mixed>
 */
function examAzureAssessment(float $pronScore): array
{
    return [
        'RecognitionStatus' => 'Success',
        'NBest' => [['Lexical' => 'apple', 'AccuracyScore' => $pronScore, 'PronScore' => $pronScore]],
    ];
}

function examWord(string $word, string $meaning): Word
{
    return Word::factory()->create(['word' => $word, 'meanings' => [['pos' => 'noun', 'meaning' => $meaning]]]);
}

describe('starting', function () {
    it('asks for the name, the class and the password of the exam', function () {
        $exam = Exam::factory()->published()->withPassword()->create(['title' => 'Kiểm tra giữa kỳ']);
        ExamQuestion::factory()->for(ExamGroup::factory()->for($exam), 'group')->create();

        $this->get(route('exams.show', $exam))
            ->assertOk()
            ->assertSee('Kiểm tra giữa kỳ')
            ->assertSee('name="student_name"', false)
            ->assertSee('name="class_name"', false)
            ->assertSee('name="password"', false);
    });

    it('hides an exam that is not published', function () {
        $exam = Exam::factory()->create();

        $this->get(route('exams.show', $exam))->assertNotFound();
        $this->post(route('exams.start', $exam), ['student_name' => 'An', 'class_name' => '3A'])->assertNotFound();
    });

    it('refuses a wrong password', function () {
        $exam = Exam::factory()->published()->withPassword('abc123')->create();

        $this->post(route('exams.start', $exam), ['student_name' => 'An', 'class_name' => '3A', 'password' => 'ABC123'])
            ->assertSessionHasErrors(['password' => 'Mật khẩu không đúng.']);

        expect(ExamAttempt::count())->toBe(0);
    });

    it('requires the name and the class', function () {
        $exam = Exam::factory()->published()->create();

        $this->post(route('exams.start', $exam), [])
            ->assertSessionHasErrors(['student_name', 'class_name'])
            ->assertSessionDoesntHaveErrors('password');
    });

    it('records the student with the right password', function () {
        $user = User::factory()->create();
        $exam = Exam::factory()->published()->withPassword('abc123')->create();

        $attempt = startExam($exam, ['password' => 'abc123']);

        expect($attempt)
            ->exam_id->toBe($exam->id)
            ->student_name->toBe('Nguyễn An')
            ->class_name->toBe('3A')
            ->user_id->toBeNull()
            ->submitted_at->toBeNull();

        $this->actingAs($user);
        $this->post(route('exams.submit', $exam));

        expect(startExam($exam, ['password' => 'abc123'])->user_id)->toBe($user->id);
    });

    it('goes on with the attempt in progress instead of starting another', function () {
        $exam = Exam::factory()->published()->create();
        $attempt = startExam($exam);

        startExam($exam, ['student_name' => 'Someone else']);

        expect(ExamAttempt::count())->toBe(1)
            ->and(ExamAttempt::sole()->is($attempt))->toBeTrue();
    });

    it('sends to the start page when no attempt was started', function () {
        $exam = Exam::factory()->published()->create();

        $this->get(route('exams.take', $exam))->assertRedirect(route('exams.show', $exam));
        $this->post(route('exams.submit', $exam))->assertRedirect(route('exams.show', $exam));
        $this->get(route('exams.result', $exam))->assertRedirect(route('exams.show', $exam));
    });
});

describe('grading', function () {
    it('shares 100 points among the questions by the weight of their group', function () {
        $exam = Exam::factory()->published()->create();
        $fillGroup = ExamGroup::factory()->for($exam)->create(['sort_order' => 1]);
        $wentQuestion = ExamQuestion::factory()->fill('went|had gone')->for($fillGroup, 'group')->create();
        $ateQuestion = ExamQuestion::factory()->fill('ate')->for($fillGroup, 'group')->create();
        $choiceGroup = ExamGroup::factory()->for($exam)->weighted(2)->create(['sort_order' => 2]);
        $choiceQuestion = ExamQuestion::factory()->for($choiceGroup, 'group')->create(['options' => ['cat', 'dog'], 'answer' => '2']);
        $attempt = startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('mỗi câu 25 điểm')
            ->assertSee('mỗi câu 50 điểm');

        $this->post(route('exams.submit', $exam), ['answers' => [
            $fillGroup->id => [$wentQuestion->id => '  Had   GONE ', $ateQuestion->id => 'eat'],
            $choiceGroup->id => [$choiceQuestion->id => '2'],
        ]])->assertRedirect(route('exams.result', $exam));

        expect($attempt->fresh())
            ->score->toBe(75.0)
            ->submitted_at->not->toBeNull()
            ->answers->toContain(['group_id' => $fillGroup->id, 'key' => (string) $ateQuestion->id, 'answer' => 'eat', 'correct' => false, 'points' => 0]);

        $this->get(route('exams.result', $exam))
            ->assertOk()
            ->assertSee('75')
            ->assertSee('1/2');
    });

    it('numbers the options of a written choice question and marks the parts in backticks', function () {
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->create();
        $question = ExamQuestion::factory()->for($group, 'group')->create([
            'prompt' => 'Which `a` is pronounced <differently>?',
            'options' => ['c`a`t', 'n`a`me'],
            'answer' => '2',
        ]);
        $attempt = startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('Which <strong><u>a</u></strong> is pronounced &lt;differently&gt;?', false)
            ->assertSee('value="1"', false)
            ->assertSee('n<strong><u>a</u></strong>me', false);

        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$question->id => '2']]]);

        expect($attempt->fresh()->score)->toBe(100.0);
    });

    it('shows the rich instructions of written questions only', function () {
        $apple = examWord('apple', 'quả táo');
        $exam = Exam::factory()->published()->create();
        ExamQuestion::factory()->for(ExamGroup::factory()->for($exam)->state(['instructions' => '<p>Look at the <strong>picture</strong>.</p><img src="/storage/exams/instructions/cat.png">']), 'group')->create();
        ExamGroup::factory()->for($exam)->forWords(ExamGroupType::MeaningToWord, [$apple])->create(['instructions' => '<p>Old typing instructions</p>']);
        startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('Look at the <strong>picture</strong>.', false)
            ->assertSee('exams/instructions/cat.png', false)
            ->assertDontSee('Old typing instructions');
    });

    it('grades the word groups from the words table', function () {
        $apple = examWord('apple', 'quả táo');
        $cat = examWord('cat', 'con mèo');
        $exam = Exam::factory()->published()->create();
        $typing = ExamGroup::factory()->for($exam)->forWords(ExamGroupType::MeaningToWord, [$apple, $cat])->create();
        $choosing = ExamGroup::factory()->for($exam)->forWords(ExamGroupType::WordToMeaning, [$apple])->create();
        $listening = ExamGroup::factory()->for($exam)->forWords(ExamGroupType::ListenToMeaning, [$cat])->create();
        $attempt = startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('(n) quả táo')
            ->assertSee('(n) con mèo')
            ->assertSee('data-exam-listen="'.$listening->id.':'.$cat->id.'" data-plays-left="3"', false)
            ->assertSee('Còn 3 lượt nghe');

        $this->post(route('exams.submit', $exam), ['answers' => [
            $typing->id => [$apple->id => 'Apple', $cat->id => 'dog'],
            $choosing->id => [$apple->id => '(n) quả táo'],
            $listening->id => [$cat->id => '(n) quả táo'],
        ]]);

        expect($attempt->fresh()->score)->toBe(50.0);
    });

    it('grades the words to fill in of a passage', function () {
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->listenAndFill('Tom has a `dog`. It likes to `run`.')->create();
        $attempt = startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('Tom has a ', false)
            ->assertSee('name="answers['.$group->id.'][2]"', false)
            ->assertDontSee('`dog`', false);

        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [1 => 'Dog', 2 => 'walk']]]);

        expect($attempt->fresh()->score)->toBe(50.0);
    });

    it('does not take a second submission', function () {
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->create();
        $question = ExamQuestion::factory()->fill('went')->for($group, 'group')->create();
        $attempt = startExam($exam);

        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$question->id => 'go']]]);
        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$question->id => 'went']]]);

        expect($attempt->fresh()->score)->toBe(0.0);
        $this->get(route('exams.take', $exam))->assertRedirect(route('exams.result', $exam));
    });
});

describe('time limit', function () {
    it('takes the answers until the grace period after the time is up', function () {
        $exam = Exam::factory()->published()->timed(10)->create();
        $group = ExamGroup::factory()->for($exam)->create();
        $question = ExamQuestion::factory()->fill('went')->for($group, 'group')->create();
        $attempt = startExam($exam);

        $this->travel(10)->minutes();
        $this->travel(ExamAttempt::GRACE_SECONDS - 5)->seconds();
        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$question->id => 'went']]]);

        expect($attempt->fresh()->score)->toBe(100.0);
    });

    it('drops the answers sent after the grace period', function () {
        $exam = Exam::factory()->published()->timed(10)->create();
        $group = ExamGroup::factory()->for($exam)->create();
        $question = ExamQuestion::factory()->fill('went')->for($group, 'group')->create();
        $attempt = startExam($exam);

        $this->travel(10)->minutes();
        $this->travel(ExamAttempt::GRACE_SECONDS + 5)->seconds();
        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$question->id => 'went']]])
            ->assertRedirect(route('exams.result', $exam));

        expect($attempt->fresh())
            ->score->toBe(0.0)
            ->submitted_at->not->toBeNull();
    });

    it('shows the time left', function () {
        $this->freezeSecond();
        $exam = Exam::factory()->published()->timed(10)->create();
        ExamQuestion::factory()->for(ExamGroup::factory()->for($exam), 'group')->create();
        startExam($exam);

        $this->travel(3)->minutes();

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('data-seconds="420"', false)
            ->assertSee('07:00');
    });

    it('submits an overdue attempt when the paper is opened again', function () {
        $exam = Exam::factory()->published()->timed(10)->create();
        ExamQuestion::factory()->for(ExamGroup::factory()->for($exam), 'group')->create();
        $attempt = startExam($exam);

        $this->travel(20)->minutes();

        $this->get(route('exams.take', $exam))->assertRedirect(route('exams.result', $exam));
        expect($attempt->fresh()->score)->toBe(0.0);
    });
});

describe('pronunciation', function () {
    beforeEach(function () {
        config(['services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null]]);
    });

    it('scores each word in proportion to its best reading', function () {
        Http::fake(['*' => Http::sequence()->push(examAzureAssessment(60))->push(examAzureAssessment(80))->push(examAzureAssessment(30))]);
        $apple = examWord('apple', 'quả táo');
        $cat = examWord('cat', 'con mèo');
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->forWords(ExamGroupType::Pronunciation, [$apple, $cat])->create();
        $attempt = startExam($exam);

        foreach ([6, 8, 3] as $score) {
            $this->post(route('exams.pronounce', $exam), ['group_id' => $group->id, 'key' => $apple->id, 'audio' => UploadedFile::fake()->create('word.wav', 10, 'audio/wav')])
                ->assertOk()
                ->assertJson(['score' => $score]);
        }

        $this->post(route('exams.pronounce', $exam), ['group_id' => $group->id, 'key' => $apple->id, 'audio' => UploadedFile::fake()->create('word.wav', 10, 'audio/wav')])
            ->assertUnprocessable()
            ->assertJson(['message' => 'Bạn đã đọc đủ số lần.']);

        $this->post(route('exams.submit', $exam), ['answers' => [$group->id => [$apple->id => '10', $cat->id => '10']]]);

        expect($attempt->fresh()->score)->toBe(40.0);
        Http::assertSentCount(3);
    });

    it('shows only the vietnamese meaning of a word to read', function () {
        $zebra = examWord('zebra', 'con ngựa vằn');
        $exam = Exam::factory()->published()->create();
        ExamGroup::factory()->for($exam)->forWords(ExamGroupType::Pronunciation, [$zebra])->create();
        startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('(n) con ngựa vằn')
            ->assertDontSee('zebra');
    });

    it('scores a sentence read aloud against the sentence', function () {
        Http::fake(['*' => Http::response(examAzureAssessment(90))]);
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->forSentences(['I like playing football.', 'My mother is a teacher.'])->create();
        $attempt = startExam($exam);

        $this->get(route('exams.take', $exam))
            ->assertOk()
            ->assertSee('My mother is a teacher.')
            ->assertSee('data-key="2" data-max-recording-ms="15000"', false);

        $this->post(route('exams.pronounce', $exam), ['group_id' => $group->id, 'key' => 2, 'audio' => UploadedFile::fake()->create('reading.wav', 10, 'audio/wav')])
            ->assertOk()
            ->assertJson(['score' => 9, 'best' => 9, 'attemptsLeft' => 2]);

        $this->post(route('exams.submit', $exam));

        expect($attempt->fresh()->score)->toBe(45.0);
        Http::assertSent(fn ($request): bool => json_decode(base64_decode($request->header('Pronunciation-Assessment')[0]), true)['ReferenceText'] === 'My mother is a teacher.');
    });

    it('refuses a sentence number the group does not have', function () {
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->forSentences(['I like playing football.'])->create();
        startExam($exam);

        $this->post(route('exams.pronounce', $exam), ['group_id' => $group->id, 'key' => 2, 'audio' => UploadedFile::fake()->create('reading.wav', 10, 'audio/wav')])
            ->assertUnprocessable();
    });

    it('refuses a word that is not in a pronunciation group of the exam', function () {
        $apple = examWord('apple', 'quả táo');
        $exam = Exam::factory()->published()->create();
        $group = ExamGroup::factory()->for($exam)->forWords(ExamGroupType::MeaningToWord, [$apple])->create();
        startExam($exam);

        $this->post(route('exams.pronounce', $exam), ['group_id' => $group->id, 'key' => $apple->id, 'audio' => UploadedFile::fake()->create('word.wav', 10, 'audio/wav')])
            ->assertUnprocessable();
    });
});
