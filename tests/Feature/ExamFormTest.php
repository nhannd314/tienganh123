<?php

use App\Enums\ExamGroupType;
use App\Enums\ExamQuestionType;
use App\Filament\Resources\Exams\Pages\CreateExam;
use App\Filament\Resources\Exams\Pages\EditExam;
use App\Jobs\FetchWordPronunciation;
use App\Jobs\GenerateSentenceAudio;
use App\Models\Course;
use App\Models\Exam;
use App\Models\ExamGroup;
use App\Models\Sentence;
use App\Models\User;
use App\Models\Word;
use Filament\Forms\Components\Repeater;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Queue::fake([FetchWordPronunciation::class]);
    $this->actingAs(User::factory()->create());
});

it('saves an exam with its groups, questions and words in order', function () {
    $undoRepeaterFake = Repeater::fake();
    $course = Course::factory()->create();
    $apple = Word::factory()->create(['word' => 'apple']);
    $cat = Word::factory()->create(['word' => 'cat']);

    Livewire::test(CreateExam::class)
        ->fillForm([
            'course_id' => $course->id,
            'title' => 'Kiểm tra cuối kỳ',
            'password' => 'abc123',
            'duration_minutes' => 45,
            'groups' => [
                [
                    'type' => ExamGroupType::Questions->value,
                    'title' => 'Ngữ pháp',
                    'instructions' => 'Chọn đáp án đúng.',
                    'weight' => 2,
                    'questions' => [
                        ['type' => ExamQuestionType::Choice->value, 'prompt' => 'She ___ a cat.', 'options' => ['have', 'has'], 'answer' => 'b'],
                        ['type' => ExamQuestionType::Fill->value, 'prompt' => 'I ___ (go) home yesterday.', 'answer' => 'went'],
                    ],
                ],
                [
                    'type' => ExamGroupType::MeaningToWord->value,
                    'title' => 'Từ vựng',
                    'weight' => 1,
                    'word_ids' => "Cat\napple, cat",
                ],
                [
                    'type' => ExamGroupType::SentencePronunciation->value,
                    'title' => 'Đọc câu',
                    'weight' => 1,
                    'sentences' => "I like  football.\n\nMy mother is a teacher.\n",
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    $exam = Exam::sole();
    [$grammar, $vocabulary, $reading] = $exam->groups;

    expect($exam)
        ->slug->toBe('kiem-tra-cuoi-ky')
        ->password->toBe('abc123')
        ->duration_minutes->toBe(45)
        ->and($grammar)
        ->type->toBe(ExamGroupType::Questions)
        ->weight->toBe(2.0)
        ->and($grammar->questions->pluck('answer')->all())->toBe(['b', 'went'])
        ->and($grammar->questions[0]->options)->toBe(['have', 'has'])
        ->and($vocabulary->word_ids)->toBe([$cat->id, $apple->id])
        ->and($reading->sentences)->toBe(['I like football.', 'My mother is a teacher.']);
});

it('requires the answer of a choice question to be the letter of one of its options', function () {
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateExam::class)
        ->fillForm([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Kiểm tra',
            'groups' => [
                [
                    'type' => ExamGroupType::Questions->value,
                    'title' => 'Ngữ pháp',
                    'weight' => 1,
                    'questions' => [
                        ['type' => ExamQuestionType::Choice->value, 'prompt' => 'She ___ a cat.', 'options' => ['have', 'has'], 'answer' => 'c'],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['groups.0.questions.0.answer' => 'Đáp án đúng phải là chữ cái của một lựa chọn, từ a đến b.']);

    $undoRepeaterFake();
});

it('refuses words that are not in the words table', function () {
    $undoRepeaterFake = Repeater::fake();
    Word::factory()->create(['word' => 'apple']);

    Livewire::test(CreateExam::class)
        ->fillForm([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Kiểm tra',
            'groups' => [
                ['type' => ExamGroupType::Pronunciation->value, 'title' => 'Phát âm', 'weight' => 1, 'word_ids' => "apple\nunicorn"],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['groups.0.word_ids']);

    $undoRepeaterFake();

    expect(Exam::count())->toBe(0);
});

it('requires a passage with words in backticks for a listen and fill group', function () {
    $undoRepeaterFake = Repeater::fake();

    Livewire::test(CreateExam::class)
        ->fillForm([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Kiểm tra',
            'groups' => [
                ['type' => ExamGroupType::ListenAndFill->value, 'title' => 'Nghe', 'weight' => 1, 'passage' => 'No word to fill in.'],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['groups.0.passage', 'groups.0.audio']);

    $undoRepeaterFake();
});

it('stores the sentences of a listen and fill sentences group, reusing the ones stored and voicing the new ones', function () {
    Queue::fake();
    $undoRepeaterFake = Repeater::fake();
    $stored = Sentence::factory()->create(['sentence' => 'I have an apple.', 'audio' => 'lessons/sentence-audio/apple.mp3']);

    Livewire::test(CreateExam::class)
        ->fillForm([
            'course_id' => Course::factory()->create()->id,
            'title' => 'Kiểm tra',
            'groups' => [
                ['type' => ExamGroupType::ListenSentenceAndFill->value, 'title' => 'Nghe câu', 'weight' => 1, 'sentences' => 'I have an `apple`.
My mother is a teacher.
'],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $undoRepeaterFake();

    expect(ExamGroup::first()->sentences)->toBe(['I have an `apple`.', 'My mother is a teacher.'])
        ->and(Sentence::count())->toBe(2)
        ->and($stored->fresh()->audio)->toBe('lessons/sentence-audio/apple.mp3');

    $created = Sentence::firstWhere('sentence', 'My mother is a teacher.');
    Queue::assertPushed(GenerateSentenceAudio::class, 1);
    Queue::assertPushed(GenerateSentenceAudio::class, fn (GenerateSentenceAudio $job): bool => $job->sentence->is($created));
});

it('shows the words of a group one per line when editing', function () {
    $apple = Word::factory()->create(['word' => 'apple']);
    $cat = Word::factory()->create(['word' => 'cat']);
    $group = ExamGroup::factory()->forWords(ExamGroupType::WordToMeaning, [$cat, $apple])->create();

    $groups = Livewire::test(EditExam::class, ['record' => $group->exam->getRouteKey()])->get('data.groups');

    expect(collect($groups)->first()['word_ids'])->toBe("cat\napple");
});
