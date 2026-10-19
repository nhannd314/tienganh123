<?php

use App\Models\Course;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function sentenceSetWithSentences(array $attributes, array $sentences): SentenceSet
{
    $set = SentenceSet::factory()->create($attributes);

    foreach ($sentences as $index => $sentence) {
        Sentence::factory()->hasAttached($set, ['sort_order' => $index + 1], 'sentenceSets')->create(['translation' => null, ...$sentence]);
    }

    return $set;
}

it('lists the sentence sets that have sentences as cards with their tag, sentence count and the first 3 sentences', function () {
    $tag = Tag::factory()->create(['name' => 'Trái cây']);
    $set = sentenceSetWithSentences(['title' => 'Bộ câu về trái cây', 'sort_order' => 1], [
        ['sentence' => 'I eat an `apple`.'], ['sentence' => 'Water is cold.'], ['sentence' => 'It is red.'], ['sentence' => 'Hidden fourth.'],
    ]);
    $set->tags()->attach([$tag->id, Tag::factory()->create(['name' => 'Đồ ăn'])->id]);
    SentenceSet::factory()->create(['title' => 'Bộ câu rỗng']);

    $this->get(route('sentences.index'))
        ->assertOk()
        ->assertSeeInOrder(['Bộ câu về trái cây', '4 câu', 'Trái cây', 'Đồ ăn', 'I eat an apple.', 'Water is cold.', 'It is red.', '+1'])
        ->assertDontSee('Hidden fourth.')
        ->assertDontSee('Bộ câu rỗng')
        ->assertSee(route('sentences.show', $set), false);
});

it('filters the sentence sets by tag', function () {
    $animals = Tag::factory()->create(['name' => 'Animals', 'slug' => 'animals']);
    $food = Tag::factory()->create(['name' => 'Food', 'slug' => 'food']);
    sentenceSetWithSentences(['title' => 'Animal set'], [['sentence' => 'A cat.']])->tags()->attach($animals);
    sentenceSetWithSentences(['title' => 'Food set'], [['sentence' => 'A cake.']])->tags()->attach($food);
    sentenceSetWithSentences(['title' => 'Both set'], [['sentence' => 'A fish.']])->tags()->attach([$animals->id, $food->id]);

    $this->get(route('sentences.index', ['tag' => 'animals']))->assertSee('Animal set')->assertSee('Both set')->assertDontSee('Food set');
});

it('tells when there are no sentences yet', function () {
    $this->get(route('sentences.index'))->assertOk()->assertSee('Chưa có câu mẫu nào');
});

it('shows a sentence set as an example sentences card', function () {
    $tag = Tag::factory()->create(['name' => 'Trái cây']);
    $set = sentenceSetWithSentences(['title' => 'Bộ câu về trái cây', 'description' => 'Học nói về trái cây'], [
        ['sentence' => 'I eat an apple.', 'translation' => 'Tôi ăn táo.'], ['sentence' => 'Water is cold.'],
    ]);
    $set->tags()->attach($tag);

    $this->get(route('sentences.show', $set))
        ->assertOk()
        ->assertSeeInOrder(['Bộ câu về trái cây', 'Học nói về trái cây', 'Trái cây'])
        ->assertSeeInOrder(['data-chant-open', 'data-example-toggle', 'I eat an apple.', 'Tôi ăn táo.', 'Water is cold.'], false);
});

it('does not show a sentence set that has no sentences', function () {
    $this->get(route('sentences.show', SentenceSet::factory()->create()))->assertNotFound();
});

it('adds the sentences page to the header navigation right after Flashcard, and moves the blog to the footer', function () {
    $html = $this->get(route('home'))->assertSeeInOrder(['Flashcard', 'Câu mẫu'])->getContent();

    expect(explode('<footer', $html)[0])->not->toContain('Blog tiếng Anh')->toContain('icon-file-text')
        ->and(explode('<footer', $html)[1])->toContain('Blog tiếng Anh', 'Câu mẫu', 'Flashcard');
});

it('lists in the sidebar the courses, vocabularies, flashcard sets and sentence sets related to the sentence set', function () {
    $tag = Tag::factory()->create();
    $set = sentenceSetWithSentences(['title' => 'Main set'], [['sentence' => 'I eat an apple.']]);
    $set->tags()->attach($tag);
    $sameTopic = sentenceSetWithSentences(['title' => 'Same topic set'], [['sentence' => 'A cat.']]);
    $sameTopic->tags()->attach($tag);
    sentenceSetWithSentences(['title' => 'Other topic set'], [['sentence' => 'A dog.']]);

    $course = Course::factory()->published()->create(['title' => 'Used course']);
    Course::factory()->published()->create(['title' => 'Other course']);
    $lesson = Lesson::factory()->published()->for(Unit::factory()->for($course))->create(['sentence_set_id' => $set->id]);
    $lessonVocabulary = Vocabulary::factory()->create(['title' => 'Lesson vocabulary']);
    $lesson->vocabularies()->attach($lessonVocabulary, ['sort_order' => 1]);
    Vocabulary::factory()->create(['title' => 'Own vocabulary', 'sentence_set_id' => $set->id]);
    Vocabulary::factory()->create(['title' => 'Unrelated vocabulary']);
    $lessonFlashcardSet = FlashcardSet::factory()->create(['title' => 'Lesson flashcards']);
    $lesson->flashcardSets()->attach($lessonFlashcardSet, ['sort_order' => 1]);
    FlashcardSet::factory()->create(['title' => 'Own flashcards', 'sentence_set_id' => $set->id]);
    FlashcardSet::factory()->create(['title' => 'Unrelated flashcards']);

    $this->get(route('sentences.show', $set))
        ->assertSeeInOrder(['Khóa học liên quan', 'Used course', 'Bộ từ vựng liên quan', 'Lesson vocabulary', 'Own vocabulary', 'Flashcard liên quan', 'Lesson flashcards', 'Own flashcards', 'Bộ câu mẫu cùng chủ đề', 'Same topic set'])
        ->assertDontSee('Other course')
        ->assertDontSee('Unrelated vocabulary')
        ->assertDontSee('Unrelated flashcards')
        ->assertDontSee('Other topic set');
});

it('keeps the sidebar of a sentence set with nothing related to notes, not blanks', function () {
    $set = sentenceSetWithSentences([], [['sentence' => 'I eat an apple.']]);

    $this->get(route('sentences.show', $set))
        ->assertSee('Chưa có khóa học nào.')
        ->assertSee('Chưa có từ vựng nào.')
        ->assertSee('Chưa có bộ flashcard nào.')
        ->assertSee('Chưa có bộ câu mẫu nào.');
});
