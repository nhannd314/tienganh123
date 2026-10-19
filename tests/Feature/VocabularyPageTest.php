<?php

use App\Models\Course;
use App\Models\Lesson;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Tag;
use App\Models\Unit;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function publishedLessonWithVocabularies(Course $course, Vocabulary ...$vocabularies): Lesson
{
    $lesson = Lesson::factory()->published()->for(Unit::factory()->for($course))->create();
    $lesson->vocabularies()->attach(collect($vocabularies)->pluck('id')->all());

    return $lesson;
}

it('links each vocabulary card of the index to its page', function () {
    $vocabulary = Vocabulary::factory()->create(['title' => 'Fruits']);

    $this->get(route('vocabularies.index'))->assertSee(route('vocabularies.show', $vocabulary), false);
});

it('shows the vocabulary with its word table and tags', function () {
    $vocabulary = Vocabulary::factory()->create(['title' => 'Fruits']);
    $vocabulary->tags()->attach(Tag::factory()->create(['name' => 'Grade 1']));
    $vocabulary->words()->attach(Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']]])->id, ['sort_order' => 1]);

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertOk()
        ->assertSeeInOrder(['Fruits', 'Grade 1', 'Từ vựng', 'apple', 'quả táo']);
});

it('lists the vocabularies of the same topic in the sidebar, without the vocabulary itself', function () {
    $tag = Tag::factory()->create();
    $vocabulary = Vocabulary::factory()->create(['title' => 'Main set']);
    Vocabulary::factory()->create(['title' => 'Same tag set'])->tags()->attach($tag);
    Vocabulary::factory()->create(['title' => 'Other tag set'])->tags()->attach(Tag::factory()->create());
    $vocabulary->tags()->attach($tag);

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertSee('Từ vựng cùng chủ đề')
        ->assertSee('Same tag set')
        ->assertDontSee('Other tag set');
});

it('lists the vocabularies used in the same courses in the sidebar', function () {
    $vocabulary = Vocabulary::factory()->create(['title' => 'Main set']);
    $sibling = Vocabulary::factory()->create(['title' => 'Sibling set']);
    $stranger = Vocabulary::factory()->create(['title' => 'Stranger set']);
    publishedLessonWithVocabularies(Course::factory()->published()->create(), $vocabulary, $sibling);
    publishedLessonWithVocabularies(Course::factory()->published()->create(), $stranger);

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertSee('Từ vựng liên quan')
        ->assertSee('Sibling set')
        ->assertDontSee('Stranger set');
});

it('always shows the sidebar widgets, with a note when they have nothing to list', function () {
    $vocabulary = Vocabulary::factory()->create();

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertOk()
        ->assertSee('Từ vựng cùng chủ đề')
        ->assertSee('Từ vựng liên quan')
        ->assertSee('Chưa có từ vựng nào.');
});

it('lists the published courses that use the vocabulary in the sidebar', function () {
    $vocabulary = Vocabulary::factory()->create();
    publishedLessonWithVocabularies(Course::factory()->published()->create(['title' => 'Used course']), $vocabulary);
    publishedLessonWithVocabularies(Course::factory()->published()->create(['title' => 'Other course']), Vocabulary::factory()->create());

    $this->get(route('vocabularies.show', $vocabulary))
        ->assertSeeInOrder(['Khóa học liên quan', 'Used course'])
        ->assertDontSee('Other course');
});

it('shows the example sentences of the sentence set of the vocabulary, and nothing when it has none', function () {
    $set = SentenceSet::factory()->create();
    Sentence::factory()->hasAttached($set, ['sort_order' => 1], 'sentenceSets')->create(['sentence' => 'I eat an apple.', 'translation' => 'Tôi ăn táo.']);
    $with = Vocabulary::factory()->create(['sentence_set_id' => $set->id]);
    $without = Vocabulary::factory()->create();

    $this->get(route('vocabularies.show', $with))->assertSeeInOrder(['data-example-toggle', 'I eat an apple.', 'Tôi ăn táo.'], false);
    $this->get(route('vocabularies.show', $without))->assertDontSee('data-example', false);
});
