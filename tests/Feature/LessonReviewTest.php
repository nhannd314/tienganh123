<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\LessonReview;
use App\Models\Unit;
use App\Models\User;
use App\Models\Word;
use App\Services\LessonScoreCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

function reviewLesson(int $wordCount = 5): Lesson
{
    $course = Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']);
    $unit = Unit::factory()->published()->for($course)->create();
    $lesson = Lesson::factory()->published()->for($unit)->create(['title' => 'Chào hỏi bạn mới']);

    attachWords($lesson, Word::factory()->count($wordCount)->create());

    return $lesson->fresh();
}

function signIn(): User
{
    return tap(User::factory()->create(), fn (User $user) => test()->actingAs($user));
}

function reviewUrl(Lesson $lesson, int|string $step = 1, string $name = 'lessons.review.show'): string
{
    return route($name, [$lesson->unit->course, $lesson->unit, $lesson, $step]);
}

/**
 * @return array<int, array{id: int, prompt: string, options: list<string>, answer: int}>
 */
function reviewQuestions(string $html): array
{
    preg_match('/<script type="application\/json" data-review-config>(.*?)<\/script>/s', $html, $match);

    return json_decode($match[1], true)['questions'];
}

it('shows part 1 with the lesson words as questions and the restart button hidden', function () {
    $lesson = reviewLesson(5);

    $response = $this->get(reviewUrl($lesson))
        ->assertOk()
        ->assertSee('Ôn tập Chào hỏi bạn mới')
        ->assertSee('Chọn nghĩa đúng cho mỗi từ nhé!')
        ->assertSeeInOrder(['Trang chủ', 'Khóa học', 'Tiếng Anh lớp 3', 'Chào hỏi bạn mới', 'Ôn tập'])
        ->assertSee('data-review', false)
        ->assertSee('Điểm: <b data-correct-count class="font-semibold text-fg-score">0</b>', false)
        ->assertDontSee('Đúng:')
        ->assertSee('Trả lời nghĩa')
        ->assertSee('Nhớ phát âm')
        ->assertSee('Hoàn thành.')
        ->assertSee('data-result hidden', false)
        ->assertSeeInOrder(['data-result hidden', 'data-review-restart', 'Làm lại từ đầu', 'Phần tiếp theo'], false)
        ->assertSee(reviewUrl($lesson, 2), false)
        ->assertDontSee('Đã lưu kết quả');

    expect(substr_count($response->getContent(), 'data-review-restart="'))->toBe(1)
        ->and(reviewQuestions($response->getContent()))->toHaveCount(5);
});

it('gives each question four distinct options that include the right meaning of the word', function () {
    $lesson = reviewLesson(5);

    $questions = reviewQuestions($this->get(reviewUrl($lesson))->getContent());

    foreach ($questions as $question) {
        $word = Word::find($question['id']);

        expect($question['options'])->toHaveCount(4)
            ->and(array_unique($question['options']))->toHaveCount(4)
            ->and($question['options'][$question['answer']])->toBe($word->allMeaningsLabel())
            ->and($question['prompt'])->toBe($word->word);
    }
});

it('shows part 2 with the meaning of each word and four distinct English words to pick from', function () {
    $lesson = reviewLesson(5);

    $response = $this->get(reviewUrl($lesson, 2))
        ->assertOk()
        ->assertSee('Chọn từ tiếng Anh đúng nhé!')
        ->assertSee(reviewUrl($lesson, 3), false);

    $questions = reviewQuestions($response->getContent());

    expect($questions)->toHaveCount(5);

    foreach ($questions as $question) {
        $word = Word::find($question['id']);

        expect($question['prompt'])->toBe($word->allMeaningsLabel())
            ->and($question['phonetic'])->toBeNull()
            ->and($question['options'])->toHaveCount(4)
            ->and(array_unique(array_map('mb_strtolower', $question['options'])))->toHaveCount(4)
            ->and($question['options'][$question['answer']])->toBe($word->word);
    }

    $response->assertDontSee('data-audio-button', false);
});

it('borrows wrong words from outside the lesson in part 2 when the lesson has too few', function () {
    $lesson = reviewLesson(2);
    Word::factory()->count(3)->create();

    $questions = reviewQuestions($this->get(reviewUrl($lesson, 2))->getContent());

    expect($questions)->toHaveCount(2)->and($questions[0]['options'])->toHaveCount(4);
});

it('grades part 2 on the English word, not on a meaning', function () {
    signIn();
    $lesson = reviewLesson(3);
    [$first, $second, $third] = $lesson->words;

    $this->postJson(reviewUrl($lesson, 2, 'lessons.review.store'), ['duration' => 30, 'answers' => [
        ['word_id' => $first->id, 'selected' => $first->word],
        ['word_id' => $second->id, 'selected' => $second->allMeaningsLabel()],
        ['word_id' => $third->id, 'selected' => $first->word],
    ]])
        ->assertCreated()
        ->assertJson(['total' => 3, 'correct' => 1, 'progress' => ['completedParts' => 0]]);

    expect(LessonReview::sole()->step)->toBe(2);
});

it('shows part 3 with the word to listen to, hidden, and its meaning to pick', function () {
    $lesson = reviewLesson(5);

    $response = $this->get(reviewUrl($lesson, 3))
        ->assertOk()
        ->assertSee('Nghe thật kỹ rồi chọn nghĩa đúng nhé!')
        ->assertSee('aria-label="Nghe từ"', false)
        ->assertSee('data-prompt-listen', false)
        ->assertDontSee('data-prompt-word', false)
        ->assertSee('"autoListen":true', false);

    $questions = reviewQuestions($response->getContent());

    expect($questions)->toHaveCount(5);

    foreach ($questions as $question) {
        $word = Word::find($question['id']);

        expect($question['prompt'])->toBe($word->word)
            ->and($question['phonetic'])->toBeNull()
            ->and($question['options'])->toHaveCount(4)
            ->and($question['options'][$question['answer']])->toBe($word->allMeaningsLabel());

        $response->assertDontSee('Nghe phát âm '.$word->word);
    }
});

it('grades part 3 on the meaning of the heard word', function () {
    signIn();
    $lesson = reviewLesson(2);
    [$first, $second] = $lesson->words;

    $this->postJson(reviewUrl($lesson, 3, 'lessons.review.store'), ['duration' => 20, 'answers' => [
        ['word_id' => $first->id, 'selected' => $first->allMeaningsLabel()],
        ['word_id' => $second->id, 'selected' => $second->word],
    ]])
        ->assertCreated()
        ->assertJson(['total' => 2, 'correct' => 1]);

    expect(LessonReview::sole()->step)->toBe(3);
});

it('shows every meaning of a word in parts 1, 2 and 3 and grades all of them as right', function (int $step) {
    signIn();
    $lesson = reviewLesson(4);
    $word = $lesson->words->first();
    $word->update(['meanings' => [['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'verb', 'meaning' => 'ăn táo']]]);

    $question = collect(reviewQuestions($this->get(reviewUrl($lesson, $step))->getContent()))->firstWhere('id', $word->id);
    $allMeanings = "(noun) quả táo\n(verb) ăn táo";

    if ($step === 2) {
        expect($question['prompt'])->toBe($allMeanings);

        return;
    }

    expect($question['options'][$question['answer']])->toBe($allMeanings)
        ->and($question['options'])->not->toContain('(noun) quả táo');

    $answers = $lesson->words->map(fn (Word $other): array => ['word_id' => $other->id, 'selected' => $other->is($word) ? $allMeanings : 'x'])->all();

    $this->postJson(reviewUrl($lesson, $step, 'lessons.review.store'), ['duration' => 5, 'answers' => $answers])
        ->assertJson(['correct' => 1]);
})->with([1, 2, 3]);

it('asks about a lesson of a single word even when no other word exists', function (int $step) {
    $lesson = reviewLesson(1);

    $questions = reviewQuestions($this->get(reviewUrl($lesson, $step))->assertOk()->getContent());

    expect($questions)->toHaveCount(1)->and($questions[0]['options'])->toHaveCount(1);
})->with([1, 2, 3]);

it('shows part 4 with the meanings of each word and boxes to type the word into', function () {
    $lesson = reviewLesson(5);

    $response = $this->get(reviewUrl($lesson, 4))
        ->assertOk()
        ->assertSee('Gõ từ tiếng Anh đúng vào các ô nhé!')
        ->assertSee('data-slots', false)
        ->assertDontSee('data-spell-check', false)
        ->assertSee('"mode":"spell"', false)
        ->assertDontSee('data-answers', false);

    $questions = reviewQuestions($response->getContent());

    expect($questions)->toHaveCount(5);

    foreach ($questions as $question) {
        $word = Word::find($question['id']);

        expect($question)->toMatchArray(['prompt' => $word->allMeaningsLabel(), 'phonetic' => null, 'word' => $word->word]);
    }
});

it('grades part 4 on the typed word, whatever its case and spacing', function () {
    signIn();
    $lesson = reviewLesson(3);
    [$first, $second, $third] = $lesson->words;
    $first->update(['word' => 'Ice cream']);
    $second->update(['word' => 'apple']);
    $third->update(['word' => 'banana']);

    $this->postJson(reviewUrl($lesson, 4, 'lessons.review.store'), ['duration' => 40, 'answers' => [
        ['word_id' => $first->id, 'selected' => 'ice  CREAM '],
        ['word_id' => $second->id, 'selected' => 'APPLE'],
        ['word_id' => $third->id, 'selected' => 'banan'],
    ]])
        ->assertCreated()
        ->assertJson(['total' => 3, 'correct' => 2]);

    expect(LessonReview::sole())->step->toBe(4)
        ->and(collect(LessonReview::sole()->answers)->pluck('correct')->all())->toBe([true, true, false]);
});

it('borrows wrong options from other words when the lesson has too few words', function () {
    $lesson = reviewLesson(2);
    Word::factory()->count(3)->create();

    $questions = reviewQuestions($this->get(reviewUrl($lesson))->getContent());

    expect($questions)->toHaveCount(2)
        ->and($questions[0]['options'])->toHaveCount(4);
});

it('asks about at most ten words and skips words without a meaning', function () {
    $lesson = reviewLesson(12);
    $lesson->words->first()->update(['meanings' => []]);

    expect(reviewQuestions($this->get(reviewUrl($lesson))->getContent()))->toHaveCount(10);
});

it('shows an empty state when the lesson has no words', function () {
    $lesson = reviewLesson(0);

    $this->get(reviewUrl($lesson))
        ->assertOk()
        ->assertSee('Bài học này chưa có từ vựng để ôn tập.')
        ->assertDontSee('data-review-config', false);
});

it('shows part 6 with the meanings of each word and a microphone, without listening to or showing the word', function () {
    config(['services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null]]);
    $lesson = reviewLesson(3);

    $response = $this->get(reviewUrl($lesson, 6))
        ->assertOk()
        ->assertSee('Nhớ phát âm')
        ->assertSee('data-record', false)
        ->assertSee('"mode":"speak"', false)
        ->assertSee('Bấm micro rồi đọc to từ tiếng Anh của nghĩa này nhé!')
        ->assertDontSee('data-audio-button', false);

    foreach (reviewQuestions($response->getContent()) as $question) {
        $word = Word::find($question['id']);

        expect($question)->toMatchArray(['prompt' => $word->allMeaningsLabel(), 'phonetic' => null, 'word' => $word->word]);
    }
});

it('returns not found for an unknown part or an unpublished lesson, unit or course', function () {
    $lesson = reviewLesson();

    $this->get(reviewUrl($lesson, 0))->assertNotFound();
    $this->get(reviewUrl($lesson, 7))->assertNotFound();

    $lesson->update(['is_published' => false]);
    $this->get(reviewUrl($lesson))->assertNotFound();
});

it('links the review buttons of the lesson page to part 1', function () {
    $lesson = reviewLesson();

    $this->get(route('lessons.show', [$lesson->unit->course, $lesson->unit, $lesson]))
        ->assertSee(reviewUrl($lesson), false);
});

it('saves the finished part and grades the answers on the server', function () {
    $user = signIn();
    $lesson = reviewLesson(3);
    $words = $lesson->words;

    $answers = $words->map(fn (Word $word, int $index): array => [
        'word_id' => $word->id,
        'selected' => $index === 0 ? '(noun) sai rồi' : $word->meaningLabels()[0],
    ])->all();

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 95, 'answers' => $answers])
        ->assertCreated()
        ->assertJson(['total' => 3, 'correct' => 2])
        ->assertJsonMissingPath('score');

    $review = LessonReview::sole();

    expect($review)
        ->lesson_id->toBe($lesson->id)
        ->user_id->toBe($user->id)
        ->step->toBe(1)
        ->total->toBe(3)
        ->correct->toBe(2)
        ->duration_seconds->toBe(95)
        ->and(collect($review->answers)->pluck('correct')->all())->toBe([false, true, true]);
});

it('accepts any meaning of a word as correct and records the signed-in user', function () {
    $lesson = reviewLesson(1);
    $word = $lesson->words->first();
    $word->update(['meanings' => [['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'verb', 'meaning' => 'ăn táo']]]);
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), [
            'duration' => 10,
            'answers' => [['word_id' => $word->id, 'selected' => '(verb) ăn táo']],
        ])
        ->assertCreated()
        ->assertJson(['correct' => 1]);

    expect(LessonReview::sole()->user_id)->toBe($user->id);
});

it('rejects results that do not match the questions of the lesson', function (array $payload) {
    signIn();
    $lesson = reviewLesson(2);
    $other = Word::factory()->create();
    [$first, $second] = $lesson->words;

    $payload = match ($payload['case']) {
        'missing answer' => ['duration' => 5, 'answers' => [['word_id' => $first->id, 'selected' => 'x']]],
        'foreign word' => ['duration' => 5, 'answers' => [['word_id' => $first->id, 'selected' => 'x'], ['word_id' => $other->id, 'selected' => 'x']]],
        'duplicate word' => ['duration' => 5, 'answers' => [['word_id' => $first->id, 'selected' => 'x'], ['word_id' => $first->id, 'selected' => 'y']]],
        'no duration' => ['answers' => [['word_id' => $first->id, 'selected' => 'x'], ['word_id' => $second->id, 'selected' => 'x']]],
    };

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), $payload)->assertUnprocessable();

    expect(LessonReview::count())->toBe(0);
})->with([
    [['case' => 'missing answer']],
    [['case' => 'foreign word']],
    [['case' => 'duplicate word']],
    [['case' => 'no duration']],
]);

it('grades part 6 from the scores Azure gave this session', function () {
    config(['services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null]]);
    Http::fake(['*' => azureAssessment(85)]);
    signIn();
    $lesson = reviewLesson(2);
    [$first, $second] = $lesson->words;

    $this->get(reviewUrl($lesson, 6));
    pronounce($lesson, $first)->assertJson(['score' => 9, 'passed' => true]);

    $this->postJson(reviewUrl($lesson, 6, 'lessons.review.store'), ['duration' => 20, 'answers' => [
        ['word_id' => $first->id, 'selected' => '0'],
        ['word_id' => $second->id, 'selected' => '10'],
    ]])
        ->assertCreated()
        ->assertJson(['total' => 2, 'correct' => 1]);

    expect(LessonReview::sole())->step->toBe(6);
});

it('marks only the parts the signed-in user passed with at least 80% right', function () {
    $lesson = reviewLesson(2);
    $user = User::factory()->create();
    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => $user->id, 'step' => 1, 'total' => 10, 'correct' => 8]);
    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => $user->id, 'step' => 2, 'total' => 10, 'correct' => 7]);

    $this->get(reviewUrl($lesson, 2))->assertDontSee('(đã xong)');

    $html = $this->actingAs($user)->get(reviewUrl($lesson, 2))->getContent();

    expect(substr_count($html, '(đã xong)'))->toBe(1);
});

it('offers no next part after the last one', function () {
    $this->get(reviewUrl(reviewLesson(), 6))->assertOk()->assertDontSee('Phần tiếp theo');
});

it('starts the sidebar progress at zero out of the questions of all six parts', function () {
    signIn();
    $lesson = reviewLesson(5);

    $html = $this->get(reviewUrl($lesson))->assertOk()->getContent();

    expect($html)
        ->toContain('<span data-stat-text="done">0</span>/<span data-stat-total>30</span>')
        ->toContain('data-total-time class="tabular-nums">00:00<')
        ->toContain('aria-label="Hoàn thành 0%"');
});

it('returns the progress over all parts when a part is saved, completing a part from 80% right', function () {
    signIn();
    $lesson = reviewLesson(5);
    $answers = fn (int $right) => $lesson->words->values()->map(fn (Word $word, int $index): array => [
        'word_id' => $word->id,
        'selected' => $index < $right ? $word->meaningLabels()[0] : '(noun) sai rồi',
    ])->all();

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 100, 'answers' => $answers(3)])
        ->assertCreated()
        ->assertJsonPath('progress', [
            'percent' => 0, 'completedParts' => 0, 'parts' => 6, 'done' => 5, 'correct' => 3, 'wrong' => 2, 'total' => 30, 'seconds' => 100, 'pronunciation' => 0, 'pronunciationWords' => 0,
        ]);

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 70, 'answers' => $answers(4)])
        ->assertCreated()
        ->assertJsonPath('progress.correct', 4)
        ->assertJsonPath('progress.seconds', 70)
        ->assertJsonPath('progress.completedParts', 1)
        ->assertJsonPath('progress.percent', 17);

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 40, 'answers' => $answers(1)])
        ->assertCreated()
        ->assertJsonPath('progress.correct', 1)
        ->assertJsonPath('progress.completedParts', 1);

    expect(LessonReview::count())->toBe(3);
});

it('shows the progress of the signed-in user only', function () {
    $lesson = reviewLesson(5);
    $user = User::factory()->create();
    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => $user->id, 'step' => 1, 'total' => 5, 'correct' => 3, 'duration_seconds' => 125]);
    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => User::factory(), 'step' => 1, 'total' => 5, 'correct' => 5, 'duration_seconds' => 10]);

    $this->actingAs($user)->get(reviewUrl($lesson))
        ->assertSee('<span data-stat-text="correct">3</span>/<span data-stat-total>30</span>', false)
        ->assertSee('<span data-stat-text="wrong">2</span>', false)
        ->assertSee('data-total-time class="tabular-nums">02:05<', false)
        ->assertSee('aria-label="Hoàn thành 0%"', false)
        ->assertDontSee('<div class="type-body-sm text-fg-body">Hoàn thành</div>', false)
        ->assertSeeInOrder(['Điểm', '<span data-score class="type-h1 text-accent-text">0</span>', 'Thứ hạng', '<span data-rank class="type-h2 text-primary-text">—</span>'], false);
});

it('plays without saving for a guest and says results need a login', function () {
    $lesson = reviewLesson(5);

    $html = $this->get(reviewUrl($lesson))
        ->assertOk()
        ->assertSee('để lưu kết quả học tập.')
        ->assertDontSee('data-stat-text', false)
        ->assertDontSee('data-ring', false)
        ->getContent();

    expect($html)->toContain('"canSave":false');

    $this->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 5, 'answers' => [['word_id' => 1, 'selected' => 'x']]])
        ->assertUnauthorized();

    expect(LessonReview::count())->toBe(0);
});

it('tells the script a signed-in user can save', function () {
    signIn();

    $this->get(reviewUrl(reviewLesson(2)))->assertSee('"canSave":true', false);
});

/**
 * A short silent recording, as the review page uploads it: WAV, 16 kHz, 16-bit mono PCM.
 */
function fakeWav(): UploadedFile
{
    $samples = str_repeat("\0\0", 1600);
    $header = 'RIFF'.pack('V', 36 + strlen($samples)).'WAVEfmt '.pack('VvvVVvv', 16, 1, 1, 16000, 32000, 2, 16).'data'.pack('V', strlen($samples));

    return UploadedFile::fake()->createWithContent('word.wav', $header.$samples);
}

function pronounce(Lesson $lesson, Word $word): TestResponse
{
    return test()->post(
        route('lessons.review.pronounce', [$lesson->unit->course, $lesson->unit, $lesson]),
        ['word_id' => $word->id, 'audio' => fakeWav()],
        ['Accept' => 'application/json'],
    );
}

/**
 * What Azure answers for a recognised word with its overall pronunciation score out of 100.
 *
 * @return array<string, mixed>
 */
function azureAssessment(float $pronScore, string $status = 'Success'): array
{
    return [
        'RecognitionStatus' => $status,
        'NBest' => [['Lexical' => 'apple', 'AccuracyScore' => $pronScore, 'PronScore' => $pronScore]],
    ];
}

describe('part 5: pronunciation', function () {
    beforeEach(function () {
        config(['services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null]]);
    });

    it('shows each word with its IPA, a listen button and a microphone', function () {
        $lesson = reviewLesson(3);

        $response = $this->get(reviewUrl($lesson, 5))
            ->assertOk()
            ->assertSee('Nghe mẫu, rồi bấm micro và đọc to từ này nhé!')
            ->assertSee('data-record', false)
            ->assertSee('"mode":"speak"', false)
            ->assertSee('"pronunciationPassScore":7', false);

        foreach (reviewQuestions($response->getContent()) as $question) {
            $word = Word::find($question['id']);

            expect($question)->toMatchArray(['prompt' => $word->word, 'phonetic' => $word->ipa_us]);
        }
    });

    it('has Azure score a reading of the word out of 10', function () {
        Http::fake(['*' => Http::response(azureAssessment(87.4))]);
        $lesson = reviewLesson(2);
        $word = $lesson->words->first();

        pronounce($lesson, $word)
            ->assertOk()
            ->assertExactJson(['score' => 9, 'best' => 9, 'passed' => true, 'attemptsLeft' => 2]);

        Http::assertSent(function (Request $request) use ($word): bool {
            $assessment = json_decode(base64_decode($request->header('Pronunciation-Assessment')[0]), true);

            return str_starts_with($request->url(), 'https://southeastasia.stt.speech.microsoft.com/speech/recognition/conversation/cognitiveservices/v1?')
                && str_contains($request->url(), 'language=en-US')
                && $request->hasHeader('Ocp-Apim-Subscription-Key', 'speech-key')
                && str_starts_with($request->header('Content-Type')[0], 'audio/wav')
                && $assessment['ReferenceText'] === $word->word
                && $assessment['GradingSystem'] === 'HundredMark';
        });
    });

    it('gives 0 when Azure hears no word', function () {
        Http::fake(['*' => Http::response(azureAssessment(0, 'InitialSilenceTimeout'))]);
        $lesson = reviewLesson(1);

        pronounce($lesson, $lesson->words->first())->assertOk()->assertJson(['score' => 0, 'passed' => false]);
    });

    it('lets a word be read three times and keeps its best score', function () {
        Http::fake(['*' => Http::sequence()->push(azureAssessment(50))->push(azureAssessment(64))->push(azureAssessment(30))]);
        $lesson = reviewLesson(1);
        $word = $lesson->words->first();

        pronounce($lesson, $word)->assertJson(['score' => 5, 'best' => 5, 'passed' => false, 'attemptsLeft' => 2]);
        pronounce($lesson, $word)->assertJson(['score' => 6, 'best' => 6, 'attemptsLeft' => 1]);
        pronounce($lesson, $word)->assertJson(['score' => 3, 'best' => 6, 'passed' => false, 'attemptsLeft' => 0]);
        pronounce($lesson, $word)->assertUnprocessable();

        Http::assertSentCount(3);
    });

    it('scores only the words of the lesson, only once Azure is configured, and keeps the reading when Azure fails', function () {
        $lesson = reviewLesson(1);
        $word = $lesson->words->first();

        Http::fake(['*' => Http::sequence()->push(['error' => 'unauthorized'], 401)->push(azureAssessment(90))]);
        pronounce($lesson, Word::factory()->create())->assertUnprocessable();
        pronounce($lesson, $word)->assertStatus(502);
        pronounce($lesson, $word)->assertOk()->assertJson(['attemptsLeft' => 2]);

        config(['services.azure_speech.key' => null]);
        pronounce($lesson, $word)->assertStatus(503);
    });

    it('rejects a recording that is not a WAV file', function () {
        Http::fake();
        $lesson = reviewLesson(1);

        $this->post(
            route('lessons.review.pronounce', [$lesson->unit->course, $lesson->unit, $lesson]),
            ['word_id' => $lesson->words->first()->id, 'audio' => UploadedFile::fake()->create('word.mp3', 10, 'audio/mpeg')],
            ['Accept' => 'application/json'],
        )->assertUnprocessable()->assertJsonValidationErrors('audio');

        Http::assertNothingSent();
    });

    it('saves part 5 with the best scores Azure gave, right from 7 out of 10, whatever the page sends', function () {
        signIn();
        Http::fake(['*' => Http::sequence()->push(azureAssessment(72))->push(azureAssessment(55))]);
        $lesson = reviewLesson(3);
        [$first, $second, $third] = $lesson->words;

        $this->get(reviewUrl($lesson, 5));
        pronounce($lesson, $first);
        pronounce($lesson, $second);

        $this->postJson(reviewUrl($lesson, 5, 'lessons.review.store'), ['duration' => 30, 'answers' => [
            ['word_id' => $first->id, 'selected' => '0'],
            ['word_id' => $second->id, 'selected' => '10'],
            ['word_id' => $third->id, 'selected' => '10'],
        ]])
            ->assertCreated()
            ->assertJson(['total' => 3, 'correct' => 1]);

        expect(LessonReview::sole())->step->toBe(5)
            ->and(collect(LessonReview::sole()->answers)->map(fn (array $answer): array => ['selected' => $answer['selected'], 'correct' => $answer['correct']])->all())->toBe([
                ['selected' => '7', 'correct' => true],
                ['selected' => '6', 'correct' => false],
                ['selected' => '0', 'correct' => false],
            ])
            ->and(session('lesson-review.pronunciation.'.$lesson->id))->toBeNull();
    });

    it('starts the readings again when part 5 is opened again', function () {
        Http::fake(['*' => Http::response(azureAssessment(40))]);
        $lesson = reviewLesson(1);
        $word = $lesson->words->first();

        foreach (range(1, 3) as $reading) {
            pronounce($lesson, $word);
        }

        pronounce($lesson, $word)->assertUnprocessable();

        $this->get(reviewUrl($lesson, 5))->assertOk();

        pronounce($lesson, $word)->assertOk()->assertJson(['attemptsLeft' => 2]);
    });
});

it('shows a single US English listen button on part 5', function () {
    $lesson = reviewLesson();
    signIn();

    $content = $this->get(reviewUrl($lesson, 5))->assertOk()->getContent();

    expect(substr_count($content, 'data-audio-button'))->toBe(1)
        ->and($content)->toContain('data-lang="en-US"');
});

it('averages the pronunciation scores of parts 5 and 6 in the progress', function () {
    $user = User::factory()->create();
    $lesson = Lesson::factory()->create();

    expect($lesson->reviewProgress($user)['pronunciation'])->toBe(0.0);

    foreach ([5 => ['8', '8'], 6 => ['9', '10']] as $step => $scores) {
        LessonReview::factory()->create([
            'lesson_id' => $lesson->id,
            'user_id' => $user->id,
            'step' => $step,
            'answers' => collect($scores)->map(fn (string $score, int $index): array => ['word_id' => $index, 'selected' => $score, 'correct' => true])->all(),
        ]);
    }

    expect($lesson->reviewProgress($user)['pronunciation'])->toBe(8.8);
});

it('scores a lesson from right and wrong answers, pronunciation, speed and completion', function () {
    $calculator = new LessonScoreCalculator;
    $progress = ['done' => 60, 'correct' => 58, 'wrong' => 2, 'total' => 60, 'seconds' => 300, 'pronunciation' => 9.0, 'pronunciationWords' => 20];

    // 58 right (580) - 2 wrong (8) + 20 words read 2 above the pass score (80) = 652; full speed bonus 20% (130);
    // 96% of the questions right +10% (65): 847 points, 85 once divided by 10.
    expect($calculator->calculate($progress))->toBe([
        'score' => 85, 'correct' => 580, 'penalty' => 8, 'pronunciation' => 80, 'speed' => 130, 'completion' => 65,
    ]);

    $withCorrect = fn (int $correct): array => $calculator->calculate([...$progress, 'correct' => $correct, 'wrong' => 60 - $correct]);

    expect($calculator->calculate([...$progress, 'seconds' => 900])['speed'])->toBe(0)
        ->and($calculator->calculate([...$progress, 'pronunciation' => 6.0])['pronunciation'])->toBe(0)
        ->and($withCorrect(58)['completion'])->toBe(65)
        ->and($withCorrect(54)['completion'])->toBe(0 + (int) round((540 - 24 + 80) * 0.05))
        ->and($withCorrect(53)['completion'])->toBe(0)
        ->and($withCorrect(20)['speed'])->toBe(0)
        ->and($calculator->calculate(['done' => 0, 'correct' => 0, 'wrong' => 0, 'total' => 60, 'seconds' => 0, 'pronunciation' => 0.0, 'pronunciationWords' => 0])['score'])->toBe(0);
});

it('never scores below zero and gives longer lessons more points', function () {
    $calculator = new LessonScoreCalculator;
    $base = ['total' => 100, 'seconds' => 0, 'pronunciation' => 0.0, 'pronunciationWords' => 0];

    expect($calculator->calculate([...$base, 'done' => 10, 'correct' => 0, 'wrong' => 10])['score'])->toBe(0)
        ->and($calculator->calculate([...$base, 'done' => 20, 'correct' => 20, 'wrong' => 0])['score'])
        ->toBeGreaterThan($calculator->calculate([...$base, 'done' => 10, 'correct' => 10, 'wrong' => 0])['score']);
});

it('keeps the best lesson score of a user and ranks the learners', function () {
    $lesson = Lesson::factory()->create();
    $user = User::factory()->create();
    $other = User::factory()->create(['name' => 'Bạn Cùng Lớp']);

    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => $user->id, 'step' => 1, 'total' => 10, 'correct' => 10, 'duration_seconds' => 0]);
    $first = $lesson->recordScore($user)->score;
    expect($first)->toBeGreaterThan(0);

    LessonReview::factory()->create(['lesson_id' => $lesson->id, 'user_id' => $user->id, 'step' => 1, 'total' => 10, 'correct' => 2, 'duration_seconds' => 0]);
    expect($lesson->recordScore($user)->score)->toBe($first)
        ->and($lesson->scores()->count())->toBe(1);

    $lesson->scores()->create(['user_id' => $other->id, 'score' => $first + 50, 'percent' => 50]);

    expect($lesson->standingOf($user))->toBe(['score' => $first, 'rank' => 2, 'learners' => 2])
        ->and($lesson->topScores()->first()->user->name)->toBe('Bạn Cùng Lớp')
        ->and($lesson->standingOf(User::factory()->create()))->toBe(['score' => 0, 'rank' => null, 'learners' => 2]);
});

it('saves the score when a part is saved and shows the top learners in the sidebar', function () {
    $lesson = reviewLesson(3);
    $user = User::factory()->create();
    $rival = User::factory()->create(['name' => 'Đối Thủ Giỏi']);
    $lesson->scores()->create(['user_id' => $rival->id, 'score' => 9999, 'percent' => 100]);

    $answers = $lesson->reviewWords()->map(fn (Word $word): array => ['word_id' => $word->id, 'selected' => 'x'])->all();

    $this->actingAs($user)->postJson(reviewUrl($lesson, 1, 'lessons.review.store'), ['duration' => 30, 'answers' => $answers])
        ->assertCreated()
        ->assertJsonPath('standing.rank', '2/2');

    $this->actingAs($user)->get(reviewUrl($lesson))->assertSee('Đối Thủ Giỏi')->assertSee('9999');
});
