<?php

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Unit;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function publishedLesson(array $lessonAttributes = []): Lesson
{
    $course = Course::factory()->published()->create(['title' => 'Tiếng Anh lớp 3']);
    $unit = Unit::factory()->for($course)->create();

    return Lesson::factory()->published()->for($unit)->create($lessonAttributes);
}

/**
 * A sentence set holding the given sentences, in order.
 *
 * @param  list<array<string, mixed>>  $sentences
 */
function sentenceSetWith(array $sentences): SentenceSet
{
    $set = SentenceSet::factory()->create();

    foreach ($sentences as $index => $sentence) {
        Sentence::factory()->hasAttached($set, ['sort_order' => $index + 1], 'sentenceSets')->create(['translation' => null, ...$sentence]);
    }

    return $set;
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

it('returns not found when the lesson or course is unpublished', function () {
    $lesson = publishedLesson();
    $url = lessonUrl($lesson);

    $lesson->update(['is_published' => false]);
    $this->get($url)->assertNotFound();

    $lesson->update(['is_published' => true]);
    $lesson->unit->course->update(['is_published' => false]);
    $this->get($url)->assertNotFound();
});

it('does not resolve a lesson through a unit of another course', function () {
    $lesson = publishedLesson();
    $otherUnit = Unit::factory()->for(Course::factory()->published())->create();

    $this->get(route('lessons.show', [$lesson->unit->course, $otherUnit, $lesson]))->assertNotFound();
});

it('lists the new words of the lesson with their meanings', function () {
    $hello = Word::factory()->create(['word' => 'hello', 'ipa_uk' => '/həˈləʊ/', 'ipa_us' => '/həˈloʊ/', 'meanings' => [['pos' => 'interjection', 'meaning' => 'xin chào']]]);
    $friend = Word::factory()->create(['word' => 'friend', 'meanings' => [['pos' => 'noun', 'meaning' => 'người bạn']]]);
    $lesson = publishedLesson();
    attachWords($lesson, [$hello->id, $friend->id]);

    $response = $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['hello', '/həˈləʊ/', '/həˈloʊ/', '(interj)', 'xin chào', 'friend', '(n)', 'người bạn'], false);

    expect(substr_count($response->getContent(), '<article'))->toBe(0);
});

it('shows the examples of the word itself in the vocabulary table', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['meanings' => [['pos' => 'n', 'meaning' => 'xin chào']], 'examples' => [['sentence' => 'Hello, nice to meet you.', 'translation' => 'Xin chào, rất vui được gặp bạn.']]]));

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['xin chào', 'Hello, nice to meet you.', 'Xin chào, rất vui được gặp bạn.']);
});

it('lists the lesson example sentences with one toggle beside the title for every sentence and meaning', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([
        ['sentence' => 'I eat an apple every day.', 'translation' => 'Tôi ăn một quả táo mỗi ngày.', 'audio' => 'lessons/sentence-audio/apple.mp3'],
        ['sentence' => 'Water is cold.', 'translation' => null, 'audio' => null],
        ['sentence' => '', 'translation' => 'Bỏ qua', 'audio' => null],
    ])->id]);
    attachWords($lesson, Word::factory()->create(['examples' => [['sentence' => 'A word example, not a lesson sentence.']]]));

    $response = $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['Từ vựng', 'Câu mẫu', 'Ẩn câu và nghĩa', 'I eat an apple every day.', 'Water is cold.'])
        ->assertSee('Tôi ăn một quả táo mỗi ngày.')
        ->assertSee('lessons/sentence-audio/apple.mp3', false)
        ->assertDontSee('Bỏ qua');

    expect(substr_count($response->getContent(), 'data-example '))->toBe(2)
        ->and(substr_count($response->getContent(), 'data-example-toggle="'))->toBe(1)
        ->and($response->getContent())->toMatch('/<p data-example-meaning class="mt-1/')->not->toContain('invisible')
        ->and($response->getContent())->toMatch('/aria-pressed="false"[^>]*data-example-toggle|data-example-toggle[^>]*aria-pressed="false"/');
});

it('keeps the hide toggle and adds the sidebar toggle to the example sentences card when no sentence has a meaning', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([['sentence' => 'Water is cold.', 'translation' => null]])->id]);

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['Câu mẫu', 'data-example-toggle', 'data-sidebar-toggle', 'Water is cold.'], false);
});

it('puts the audio button of an example sentence to the left of its sentence, below the image', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([
        ['sentence' => 'I eat an apple.', 'image' => 'lessons/sentence-images/apple.png'],
        ['sentence' => 'Water is cold.'],
    ])->id]);

    $content = $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['data-example-image', 'lessons/sentence-images/apple.png', '</div>', 'data-text="I eat an apple."', 'text-xl', 'I eat an apple.', 'data-text="Water is cold."', 'Water is cold.'], false)
        ->getContent();

    expect(substr_count($content, 'lessons/sentence-images/'))->toBe(1)
        ->and(substr_count($content, 'data-example-image'))->toBe(1)
        ->and(substr_count($content, 'data-audio-button'))->toBe(2);
});

it('labels the words in backticks of an example sentence, with a popover for the known ones', function () {
    Word::factory()->create(['word' => 'apple', 'ipa_us' => '/ˈæp.əl/', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]);
    Word::factory()->create(['word' => 'pear', 'meanings' => [['pos' => 'n', 'meaning' => 'quả lê']]]);
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([
        ['sentence' => 'I have an `Apple` and a `pear`.'],
        ['sentence' => 'A `pineapple` is not a word here.'],
    ])->id]);

    $content = $this->get(lessonUrl($lesson))
        ->assertSee('popovertarget="example-word-0-1"', false)
        ->assertSee('popovertarget="example-word-0-3"', false)
        ->assertSee('I have an <span class="inline">', false)
        ->assertSeeInOrder(['>Apple</button>', '</span> and a <span class="inline">', '>pear</button>', '</span>.'], false)
        ->assertSee('quả táo')
        ->assertSee('A <span class="inline rounded-xs bg-primary-bg px-1.5 font-medium text-primary-text">pineapple</span> is not a word here.', false)
        ->assertSee('data-text="I have an Apple and a pear."', false)
        ->getContent();

    expect($content)->not->toContain('`')
        ->and($content)->not->toContain('example-word-1-');
});

it('hides the example sentence card when the lesson has no example sentence', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['examples' => [['sentence' => 'Water is cold.']]]));

    $this->get(lessonUrl($lesson))->assertDontSee('data-example ', false);
});

it('puts the part of speech next to each meaning and offers a full view on mobile', function () {
    $lesson = publishedLesson();
    attachWords($lesson, Word::factory()->create(['meanings' => [['pos' => 'verb', 'meaning' => 'làm việc'], ['pos' => 'noun', 'meaning' => 'công việc']]]));

    $this->get(lessonUrl($lesson))
        ->assertSeeInOrder(['(v)', 'làm việc', '(n)', 'công việc'])
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
        ->assertSee("data-copy-text=\"apple (n) quả táo\nwork (v) làm việc (n) công việc\"", false);
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
    $this->get(lessonUrl(publishedLesson(['passage' => '<p></p><p>&nbsp;</p>'])))->assertDontSee('data-passage', false);
});

it('turns inline code that matches a word into a popover and other code into a label that opens nothing', function () {
    Word::factory()->create(['word' => 'apple', 'ipa_us' => '/ˈæp.əl/', 'ipa_uk' => '/ˈæp.əl/', 'meanings' => [['pos' => 'n', 'meaning' => 'quả táo']]]);
    $lesson = publishedLesson(['content' => '<p>Một <code>Apple</code> và <code>zzyzx</code>.</p>']);

    $this->get(lessonUrl($lesson))
        ->assertSee('popovertarget="word-0"', false)
        ->assertSee('/ˈæp.əl/')
        ->assertSee('quả táo')
        ->assertSee('Một')
        ->assertSee('text-primary-text">zzyzx</span>', false)
        ->assertDontSee('<code>zzyzx</code>', false)
        ->assertDontSee('word-1', false);
});

it('renders images attached in the rich content from the public disk', function () {
    Storage::fake('public');
    Storage::disk('public')->put('lessons/content/apple.png', 'png');
    $lesson = publishedLesson(['content' => '<p>Xem hình:</p><img src="x" data-id="lessons/content/apple.png">']);

    $this->get(lessonUrl($lesson))->assertSee('/storage/lessons/content/apple.png', false);
});

it('plays a video file uploaded in the rich content through a video block', function () {
    Storage::fake('public');
    $lesson = publishedLesson(['content' => '<p>Xem:</p><div data-type="customBlock" data-id="video" data-config="{&quot;file&quot;:&quot;lessons/content-video/clip.mp4&quot;}"></div>']);

    $this->get(lessonUrl($lesson))->assertSee('<video controls', false)->assertSee('/storage/lessons/content-video/clip.mp4', false);
});

it('plays an audio file uploaded in the rich content through an audio block', function () {
    Storage::fake('public');
    $lesson = publishedLesson(['content' => '<p>Nghe:</p><div data-type="customBlock" data-id="audio" data-config="{&quot;file&quot;:&quot;lessons/content-audio/hello.mp3&quot;}"></div>']);

    $response = $this->get(lessonUrl($lesson))->assertSee('<audio controls', false)->assertSee('/storage/lessons/content-audio/hello.mp3', false);

    expect(substr_count($response->getContent(), '<audio'))->toBe(1);
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
    $unit = Unit::factory()->for($course)->create();
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
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([
        ['sentence' => 'I eat an apple.', 'audio' => 'lessons/sentence-audio/apple.mp3'],
        ['sentence' => 'Water is cold.'],
    ])->id]);

    $content = $this->get(lessonUrl($lesson))->assertDontSee('<audio', false)->getContent();

    expect(substr_count($content, 'data-src="'.Storage::disk('public')->url('lessons/sentence-audio/apple.mp3').'"'))->toBe(1)
        ->and($content)->toContain('data-text="I eat an apple."', 'data-text="Water is cold."');
});

it('shows the image placeholder on a flashcard without an image', function () {
    $lesson = publishedLesson();
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Plain', 'image' => null]);

    $this->get(lessonUrl($lesson))->assertSee('Plain')->assertSee('icon-image', false)->assertDontSee('border-dashed', false);
});

it('puts the example sentence card below the flashcards and above the content', function () {
    $lesson = publishedLesson([
        'sentence_set_id' => sentenceSetWith([['sentence' => 'Water is cold.']])->id,
    ]);
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Bộ thẻ nước']);
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Water']);

    $this->get(lessonUrl($lesson))->assertSeeInOrder(['Từ vựng', 'Bộ thẻ nước', 'Câu mẫu', 'Water is cold.', 'Nội dung bài học']);
});

it('offers a Chant popup for the flashcards and for the example sentences, with a slide for each card and the songs of public/chant', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([
        ['sentence' => 'I `eat` an apple.', 'translation' => 'Tôi ăn táo.', 'image' => 'lessons/sentence-images/apple.png'],
        ['sentence' => 'Water is cold.', 'translation' => null],
    ])->id]);
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    $word = Word::factory()->create(['word' => 'water', 'ipa_us' => '/ˈwɑː.t̬ɚ/', 'meanings' => [['pos' => 'noun', 'meaning' => 'nước']]]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => '', 'word_id' => $word->id, 'image' => 'flashcards/images/water.png']);

    $response = $this->get(lessonUrl($lesson))->assertSee('data-chant-open', false)->assertSee('data-chant-next', false)->assertSee('data-chant-progress', false)->assertDontSee('data-chant-start', false)->assertDontSee('data-chant-toggle', false)->assertDontSee('chant-seconds', false);
    $content = $response->getContent();

    preg_match_all('/<dialog\s+data-chant-dialog\s+data-slides="([^"]*)"\s+data-tracks="([^"]*)"/s', $content, $matches, PREG_SET_ORDER);
    $decode = fn (string $attribute): array => json_decode(html_entity_decode($attribute), true);

    expect($matches)->toHaveCount(2)
        ->and($decode($matches[0][1]))->toBe([[
            'image' => Storage::disk('public')->url('flashcards/images/water.png'),
            'title' => 'water',
            'ipa' => '/ˈwɑː.t̬ɚ/',
            'meaning' => 'nước',
        ]])
        ->and($decode($matches[1][1]))->toBe([
            ['image' => Storage::disk('public')->url('lessons/sentence-images/apple.png'), 'title' => 'I eat an apple.', 'meaning' => 'Tôi ăn táo.'],
            ['image' => null, 'title' => 'Water is cold.', 'meaning' => null],
        ])
        ->and($decode($matches[0][2]))->toContain(asset('chant/beat.mp3'));
});

it('has no Chant popup on a lesson without flashcards and example sentences', function () {
    $this->get(lessonUrl(publishedLesson()))->assertDontSee('data-chant-dialog', false);
});

it('draws the sentence slides of the Chant popup lighter and with a smaller meaning than the flashcard slides', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([['sentence' => 'Water is cold.', 'translation' => 'Nước lạnh.']])->id]);
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Water']);

    $content = $this->get(lessonUrl($lesson))->getContent();

    preg_match_all('/<p data-chant-title[^>]*class="([^"]*)"/', $content, $titles);
    preg_match_all('/<p data-chant-meaning class="([^"]*)"/', $content, $meanings);

    expect($titles[1])->toHaveCount(2)
        ->and($titles[1][0])->toContain('font-bold')
        ->and($titles[1][1])->toContain('font-medium')->not->toContain('font-bold')
        ->and($meanings[1][0])->toContain('text-2xl', 'md:text-4xl')
        ->and($meanings[1][1])->toContain('text-xl', 'md:text-2xl')->not->toContain('md:text-4xl');
});

it('keeps the Chant popup dark, puts the picture of a flashcard slide on a light rounded panel, and animates no picture', function () {
    $lesson = publishedLesson(['sentence_set_id' => sentenceSetWith([['sentence' => 'Water is cold.']])->id]);
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson->flashcardSets()->attach($flashcardSet, ['sort_order' => 1]);
    Flashcard::factory()->hasAttached($flashcardSet, ['sort_order' => 1])->create(['title' => 'Water']);

    $content = $this->get(lessonUrl($lesson))->getContent();

    preg_match_all('/<img data-chant-image([^>]*)>/', $content, $images);
    preg_match_all('/<div data-chant-picture class="([^"]*)"/', $content, $pictures);

    expect($pictures[1])->toHaveCount(2)
        ->and($pictures[1][0])->toContain('bg-white', 'rounded-xl')
        ->and($pictures[1][1])->not->toContain('bg-white')
        ->and($content)->toContain('bg-fg-strong')
        ->and($images[1])->toHaveCount(2)
        ->and($images[1][0])->not->toContain('data-chant-animate')
        ->and($images[1][1])->not->toContain('data-chant-animate');
});

it('embeds a YouTube video block of the content', function () {
    $lesson = publishedLesson([
        'content' => '<p>Watch</p><div data-type="customBlock" data-config="{&quot;url&quot;:&quot;https://www.youtube.com/watch?v=dQw4w9WgXcQ&quot;}" data-id="youtube_video"></div>',
    ]);

    $this->get(lessonUrl($lesson))
        ->assertOk()
        ->assertSee('<iframe src="https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ"', false);
});
