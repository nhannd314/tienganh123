<?php

use App\Models\Course;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Tag;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('lists the flashcard sets with their tags, card count and the first 3 cards', function () {
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Animals set']);
    $flashcardSet->tags()->attach(Tag::factory()->create(['name' => 'Grade 1']));
    $flashcardSet->flashcards()->attach(
        collect(['cat', 'dog', 'bird', 'fish'])
            ->mapWithKeys(fn (string $title, int $index) => [Flashcard::factory()->create(['title' => $title])->id => ['sort_order' => $index]])
            ->all()
    );

    $this->get(route('flashcards.index'))
        ->assertOk()
        ->assertSeeInOrder(['Animals set', '4 thẻ', 'Grade 1', 'cat', 'dog', 'bird', '+1'])
        ->assertDontSee('fish');
});

it('filters the flashcard sets by tag', function () {
    $animals = Tag::factory()->create(['name' => 'Animals tag', 'slug' => 'animals']);
    FlashcardSet::factory()->create(['title' => 'Pets'])->tags()->attach($animals);
    FlashcardSet::factory()->create(['title' => 'Cars']);

    $this->get(route('flashcards.index', ['tag' => 'animals']))
        ->assertSee('Pets')->assertDontSee('Cars');
});

it('shows a flashcard set with its cards', function () {
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Animals set']);
    $flashcardSet->flashcards()->attach(Flashcard::factory()->create(['title' => 'elephant'])->id, ['sort_order' => 0]);

    $this->get(route('flashcards.show', $flashcardSet))
        ->assertOk()
        ->assertSeeInOrder(['Flashcard', 'Animals set', 'elephant']);
});

it('shows the Flashcard menu instead of the tests and games menus', function () {
    $this->get(route('flashcards.index'))
        ->assertSee('Flashcard')
        ->assertDontSee('Bài kiểm tra')
        ->assertDontSee('Trò chơi');
});

it('shows the image of the first card that has one as the thumbnail of the set', function () {
    $flashcardSet = FlashcardSet::factory()->create();
    $flashcardSet->flashcards()->attach(Flashcard::factory()->create(['image' => null])->id, ['sort_order' => 0]);
    $flashcardSet->flashcards()->attach(Flashcard::factory()->create(['image' => 'flashcards/second.png'])->id, ['sort_order' => 1]);
    $flashcardSet->flashcards()->attach(Flashcard::factory()->create(['image' => 'flashcards/third.png'])->id, ['sort_order' => 2]);

    $this->get(route('flashcards.index'))
        ->assertSee('flashcards/second.png')
        ->assertDontSee('flashcards/third.png');
});

it('shows the other sets that share a tag with the set in the sidebar', function () {
    $tag = Tag::factory()->create();
    $flashcardSet = FlashcardSet::factory()->create(['title' => 'Animals set']);
    $flashcardSet->tags()->attach($tag);
    FlashcardSet::factory()->create(['title' => 'Pets set'])->tags()->attach($tag);
    FlashcardSet::factory()->create(['title' => 'Cars set']);

    $this->get(route('flashcards.show', $flashcardSet))
        ->assertSeeInOrder(['Flashcard cùng chủ đề', 'Pets set'])
        ->assertDontSee('Cars set');
});

it('lists the published courses that use the set in the sidebar', function () {
    $flashcardSet = FlashcardSet::factory()->create();
    $lesson = Lesson::factory()->published()->for(Unit::factory()->published()->for(Course::factory()->published()->create(['title' => 'Used course'])))->create();
    $lesson->flashcardSets()->attach($flashcardSet);
    Lesson::factory()->published()->for(Unit::factory()->published()->for(Course::factory()->published()->create(['title' => 'Other course'])))->create();

    $this->get(route('flashcards.show', $flashcardSet))
        ->assertSeeInOrder(['Khóa học liên quan', 'Used course'])
        ->assertDontSee('Other course');
});
