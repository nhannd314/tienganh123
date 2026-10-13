<?php

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Unit;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function publishedLesson(array $lessonAttributes = []): Lesson
{
    $course = Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']);
    $unit = Unit::factory()->published()->for($course)->create();

    return Lesson::factory()->published()->for($unit)->create($lessonAttributes);
}

function lessonUrl(Lesson $lesson): string
{
    return route('lessons.show', [$lesson->unit->course, $lesson->unit, $lesson]);
}

it('shows a published lesson with breadcrumbs back to its course', function () {
    $lesson = publishedLesson(['title' => 'Chào hỏi bạn mới', 'description' => 'Làm quen với lời chào đầu tiên.']);

    $this->get(lessonUrl($lesson))
        ->assertOk()
        ->assertSee('Chào hỏi bạn mới')
        ->assertSee('Làm quen với lời chào đầu tiên.')
        ->assertSeeInOrder(['Trang chủ', 'Khóa học', 'Tiếng Anh lớp 3', 'Chào hỏi bạn mới'])
        ->assertSee(route('courses.show', $lesson->unit->course), false);
});

it('returns not found when the lesson, unit or course is unpublished', function () {
    $lesson = publishedLesson();
    $url = lessonUrl($lesson);

    $lesson->update(['is_published' => false]);
    $this->get($url)->assertNotFound();

    $lesson->update(['is_published' => true]);
    $lesson->unit->update(['is_published' => false]);
    $this->get($url)->assertNotFound();

    $lesson->unit->update(['is_published' => true]);
    $lesson->unit->course->update(['is_published' => false]);
    $this->get($url)->assertNotFound();
});

it('does not resolve a lesson through a unit of another course', function () {
    $lesson = publishedLesson();
    $otherUnit = Unit::factory()->published()->for(Course::factory()->published())->create();

    $this->get(route('lessons.show', [$lesson->unit->course, $otherUnit, $lesson]))->assertNotFound();
});

it('lists the new words of the lesson with their meanings', function () {
    $hello = Word::factory()->create(['word' => 'hello', 'ipa_uk' => '/həˈləʊ/', 'ipa_us' => '/həˈloʊ/', 'meanings' => [['pos' => 'interjection', 'meaning' => 'xin chào']]]);
    $friend = Word::factory()->create(['word' => 'friend', 'meanings' => [['pos' => 'noun', 'meaning' => 'người bạn']]]);
    $lesson = publishedLesson();
    attachWords($lesson, [$hello->id, $friend->id]);

    $response = $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['hello', '/həˈləʊ/', '/həˈloʊ/', '(interjection)', 'xin chào', 'friend', '(noun)', 'người bạn'], false);

    expect(substr_count($response->getContent(), '<article'))->toBe(0);
});

it('shows the examples of the word itself in the vocabulary table', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['meanings' => [['pos' => 'n', 'meaning' => 'xin chào']], 'examples' => [['sentence' => 'Hello, nice to meet you.', 'translation' => 'Xin chào, rất vui được gặp bạn.']]]));

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['xin chào', 'Hello, nice to meet you.', 'Xin chào, rất vui được gặp bạn.']);
});

it('lists the lesson example sentences in their own card with a toggle for the meaning', function () {
    $lesson = publishedLesson();
    attachWords($lesson, [
        Word::factory()->create(['examples' => [[
            'sentence' => 'I eat an apple every day.',
            'translation' => 'Tôi ăn một quả táo mỗi ngày.',
            'audio' => 'audio/examples/apple.mp3',
        ]]]),
        Word::factory()->create(['examples' => [['sentence' => 'Water is cold.']]]),
        Word::factory()->create(['examples' => null]),
    ]);

    $response = $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['Từ vựng', 'Câu mẫu', 'I eat an apple every day.', 'Hiện nghĩa', 'Water is cold.'])
        ->assertSee('Tôi ăn một quả táo mỗi ngày.')
        ->assertSee('audio/examples/apple.mp3', false);

    expect(substr_count($response->getContent(), 'data-example '))->toBe(2)
        ->and(substr_count($response->getContent(), 'data-example-toggle="'))->toBe(1)
        ->and($response->getContent())->toMatch('/<p data-example-meaning class="invisible/');
});

it('labels the word of an example sentence with a popover, including a simple inflection', function () {
    $lesson = publishedLesson();
    attachWords($lesson, [
        Word::factory()->create(['word' => 'apple', 'ipa_us' => '/ˈæp.əl/', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']], 'examples' => [['sentence' => 'I ate Apples and pineapple.']]]),
        Word::factory()->create(['word' => 'water', 'examples' => [['sentence' => 'No match here.']]]),
    ]);

    $this->get(lessonUrl($lesson))
        ->assertSee('popovertarget="example-word-0"', false)
        ->assertSeeInOrder(['I ate', '>Apples</button>', 'and pineapple.'], false)
        ->assertSee('quả táo')
        ->assertSee('No match here.')
        ->assertDontSee('example-word-1', false);
});

it('hides the example sentence card when no word has an example sentence', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['examples' => null]));

    $this->get(lessonUrl($lesson))->assertDontSee('data-example', false);
});

it('puts the part of speech next to each meaning and offers a full view on mobile', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['meanings' => [['pos' => 'verb', 'meaning' => 'làm việc'], ['pos' => 'noun', 'meaning' => 'công việc']]]));

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['(verb)', 'làm việc', '(noun)', 'công việc'])
        ->assertDontSee('Từ loại')
        ->assertDontSee('Phiên âm UK')
        ->assertDontSee('Ẩn phiên âm')
        ->assertSee('data-vocab-action="expand"', false)
        ->assertSee('aria-label="Hiển thị đầy đủ"', false)
        ->assertSee('data-vocab-action="print"', false);
});

it('offers a copy of the words with their meanings, one word per line', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']]]));
    attachWords($lesson, Word::factory()->create(['word' => 'work', 'meanings' => [['pos' => 'verb', 'meaning' => 'làm việc'], ['pos' => 'noun', 'meaning' => 'công việc']]]));

    $this->get(lessonUrl($lesson))
        ->assertSee('data-vocab-action="copy"', false)
        ->assertSee("data-copy-text=\"apple (noun) quả táo\nwork (verb) làm việc (noun) công việc\"", false);
});

it('shows an empty state when the lesson has no new words', function () {
    $this->get(lessonUrl(publishedLesson()))->assertSee('Bài học này chưa có từ vựng.');
});

it('shows the flashcards of the lesson flashcard set as cards', function () {
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Đồ dùng học tập']);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Custom card']);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => null, 'word_id' => Word::factory()->create(['word' => 'zebra'])->id]);
    $lesson = publishedLesson();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);

    $response = $this->get(lessonUrl($lesson))->assertSee('Đồ dùng học tập')->assertSee('Custom card')->assertSee('zebra');

    expect(substr_count($response->getContent(), '<article'))->toBe(2)
        ->and($response->getContent())->toContain('data-print-flashcards');
});

it('hides the flashcard section when the lesson has no flashcard set or the set has no cards', function () {
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Bộ thẻ trống']);

    $lesson = publishedLesson();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);

    $this->get(lessonUrl($lesson))->assertDontSee('Bộ thẻ trống')->assertDontSee('data-flashcards', false);
    $this->get(lessonUrl(publishedLesson()))->assertDontSee('data-flashcards', false);
});

it('renders the lesson content as rich text and strips scripts', function () {
    $lesson = publishedLesson(['content' => '<h3>Cách chào</h3><p>Nói <strong>hello</strong> khi gặp bạn.</p><script>alert(1)</script>']);

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['Nội dung bài học', 'Cách chào', 'Nói', 'hello', 'khi gặp bạn.'], false)
        ->assertDontSee('<script>alert(1)</script>', false);
});

it('shows the passage above the vocabulary with word popovers and no clash with the content popovers', function () {
    Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]);
    $lesson = publishedLesson(['passage' => '<p>An <code>apple</code> a day.</p>', 'content' => '<p>Eat an <code>apple</code>.</p>']);

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['An', 'a day.', 'Từ vựng', 'Nội dung bài học'])
        ->assertSee('data-passage', false)
        ->assertSee('popovertarget="passage-word-0"', false)
        ->assertSee('popovertarget="word-0"', false)
        ->assertDontSee('<code>', false);
});

it('shows an audio player above the passage when the lesson has passage audio', function () {
    $lesson = publishedLesson(['passage' => '<p>A short story.</p>', 'passage_audio' => 'lessons/passage-audio/story.mp3']);

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['data-passage-audio', 'lessons/passage-audio/story.mp3', 'A short story.'], false);

    $this->get(lessonUrl(publishedLesson(['passage' => '<p>No audio.</p>'])))->assertDontSee('data-passage-audio', false);
});

it('hides the passage section when the lesson has no passage', function () {
    $this->get(lessonUrl(publishedLesson(['passage' => null])))->assertDontSee('data-passage', false);
});

it('turns inline code that matches a word into a popover and leaves other code as text', function () {
    Word::factory()->create(['word' => 'apple', 'ipa_us' => '/ˈæp.əl/', 'ipa_uk' => '/ˈæp.əl/', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]);
    $lesson = publishedLesson(['content' => '<p>Một <code>Apple</code> và <code>zzyzx</code>.</p>']);

    $this->get(lessonUrl($lesson))
        ->assertSee('popovertarget="word-0"', false)
        ->assertSee('/ˈæp.əl/')
        ->assertSee('quả táo')
        ->assertSee('Một')
        ->assertSee('zzyzx')
        ->assertDontSee('<code>zzyzx</code>', false)
        ->assertDontSee('word-1', false);
});

it('renders images attached in the rich content from the public disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('lessons/content/apple.png', 'png');
    $lesson = publishedLesson(['content' => '<p>Xem hình:</p><img src="x" data-id="lessons/content/apple.png">']);

    $this->get(lessonUrl($lesson))->assertSee('/storage/lessons/content/apple.png', false);
});

it('lists attachments and plays audio files inline', function () {
    $lesson = publishedLesson(['attachments' => ['lessons/attachments/hello.mp3', 'lessons/attachments/worksheet.pdf']]);

    $response = $this->get(lessonUrl($lesson))
        ->assertSee('Tài liệu đính kèm')
        ->assertSeeInOrder(['hello.mp3', 'worksheet.pdf'])
        ->assertSee('/storage/lessons/attachments/worksheet.pdf', false);

    expect(substr_count($response->getContent(), '<audio'))->toBe(1);
});

it('hides the attachments section when the lesson has none', function () {
    $this->get(lessonUrl(publishedLesson()))->assertDontSee('Tài liệu đính kèm');
});

it('links the open lessons from the course page and the locked ones to the registration', function () {
    $course = Course::factory()->priced()->published()->create();
    $unit = Unit::factory()->published()->for($course)->create();
    $free = Lesson::factory()->published()->free()->for($unit)->create(['sort_order' => 1]);
    $locked = Lesson::factory()->published()->for($unit)->create(['sort_order' => 2]);

    $this->get(route('courses.show', $course))
        ->assertSee(route('lessons.show', [$course, $unit, $free]), false)
        ->assertDontSee(route('lessons.show', [$course, $unit, $locked]), false)
        ->assertSee(route('courses.enroll', $course), false);
});

it('shows every flashcard set of the lesson as its own section, in order, skipping cards with nothing to show', function () {
    $lesson = publishedLesson();
    $second = FlashcardSet::factory()->create(['title' => 'Bộ thứ hai']);
    $first = FlashcardSet::factory()->create(['title' => 'Bộ thứ nhất']);
    $lesson->flashcardSets()->attach([$second->id => ['sort_order' => 2], $first->id => ['sort_order' => 1]]);
    Flashcard::factory()->hasAttached($first, ['sort_order' => 1])->create(['title' => 'Card A']);
    Flashcard::factory()->hasAttached($second, ['sort_order' => 1])->create(['title' => 'Card B']);
    Flashcard::factory()->hasAttached($second, ['sort_order' => 1])->create(['title' => null, 'word_id' => null]);

    $response = $this->get(lessonUrl($lesson))->assertSeeInOrder(['Bộ thứ nhất', 'Card A', 'Bộ thứ hai', 'Card B']);

    expect(substr_count($response->getContent(), '<div data-flashcards '))->toBe(2)
        ->and(substr_count($response->getContent(), '<article'))->toBe(2);
});

it('shows a flashcard with the title or the linked word, its first meaning, and plays its own audio file', function () {
    $lesson = publishedLesson();
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    $word = Word::factory()->create(['word' => 'zebra', 'meanings' => [['pos' => '', 'meaning' => 'ngựa vằn']]]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => '', 'word_id' => $word->id, 'audio' => 'flashcards/audio/zebra.mp3']);

    $this->get(lessonUrl($lesson))
        ->assertSee('zebra')
        ->assertSee('ngựa vằn')
        ->assertDontSee('() ngựa vằn')
        ->assertSee('data-src="'.Storage::disk('public')->url('flashcards/audio/zebra.mp3').'"', false);
});

it('plays the example audio file from the listen button, which speaks the sentence when there is no file', function () {
    $lesson = publishedLesson();
    attachWords($lesson, [
        Word::factory()->create(['examples' => [['sentence' => 'I eat an apple.', 'audio' => 'audio/examples/apple.mp3']]]),
        Word::factory()->create(['examples' => [['sentence' => 'Water is cold.']]]),
    ]);

    $content = $this->get(lessonUrl($lesson))->assertDontSee('<audio', false)->getContent();

    expect(substr_count($content, 'data-src="'.Storage::disk('public')->url('audio/examples/apple.mp3').'"'))->toBe(1)
        ->and($content)->toContain('data-text="I eat an apple."', 'data-text="Water is cold."');
});

it('shows the image placeholder on a flashcard without an image', function () {
    $lesson = publishedLesson();
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Plain', 'image' => null]);

    $this->get(lessonUrl($lesson))->assertSee('Plain')->assertSee('icon-image', false)->assertDontSee('border-dashed', false);
});
