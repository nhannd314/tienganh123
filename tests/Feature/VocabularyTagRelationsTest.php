<?php

use App\Models\FlashcardSet;
use App\Models\Tag;
use App\Models\Vocabulary;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('links a vocabulary to its words in order', function () {
    $vocabulary = Vocabulary::factory()->create();
    [$first, $second] = Word::factory()->count(2)->create();
    $vocabulary->words()->attach([$second->id => ['sort_order' => 2], $first->id => ['sort_order' => 1]]);

    expect($vocabulary->words->pluck('id')->all())->toBe([$first->id, $second->id])
        ->and($first->vocabularies->pluck('id')->all())->toBe([$vocabulary->id]);
});

it('tags vocabularies and flashcard sets and generates the tag slug', function () {
    $tag = Tag::factory()->create(['name' => 'Daily Life', 'slug' => '']);
    $vocabulary = Vocabulary::factory()->create();
    $flashcardSet = FlashcardSet::factory()->create();
    $vocabulary->tags()->attach($tag);
    $flashcardSet->tags()->attach($tag);

    expect($tag->slug)->toBe('daily-life')
        ->and($tag->vocabularies->pluck('id')->all())->toBe([$vocabulary->id])
        ->and($tag->flashcardSets->pluck('id')->all())->toBe([$flashcardSet->id])
        ->and($vocabulary->tags)->toHaveCount(1)
        ->and($flashcardSet->tags)->toHaveCount(1);
});
