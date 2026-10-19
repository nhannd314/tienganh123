<?php

use App\Models\FlashcardSet;
use App\Models\Lesson;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Vocabulary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shares sentences between sets, each in its own order', function () {
    $sentence = Sentence::factory()->create();
    $other = Sentence::factory()->create();
    $setA = SentenceSet::factory()->create();
    $setB = SentenceSet::factory()->create();
    $setA->sentences()->attach([$other->id => ['sort_order' => 2], $sentence->id => ['sort_order' => 1]]);
    $setB->sentences()->attach([$other->id => ['sort_order' => 1], $sentence->id => ['sort_order' => 2]]);

    expect($setA->sentences->pluck('id')->all())->toBe([$sentence->id, $other->id])
        ->and($setB->sentences->pluck('id')->all())->toBe([$other->id, $sentence->id])
        ->and($sentence->sentenceSets->pluck('id')->all())->toBe([$setA->id, $setB->id]);
});
it('links a lesson, a vocabulary and a flashcard set to one sentence set', function () {
    $set = SentenceSet::factory()->create();
    $lesson = Lesson::factory()->create(['sentence_set_id' => $set->id]);
    $vocabulary = Vocabulary::factory()->create(['sentence_set_id' => $set->id]);
    $flashcardSet = FlashcardSet::factory()->create(['sentence_set_id' => $set->id]);

    expect($lesson->sentenceSet->is($set))->toBeTrue()
        ->and($vocabulary->sentenceSet->is($set))->toBeTrue()
        ->and($flashcardSet->sentenceSet->is($set))->toBeTrue()
        ->and($set->lessons->modelKeys())->toBe([$lesson->id])
        ->and($set->vocabularies->modelKeys())->toBe([$vocabulary->id])
        ->and($set->flashcardSets->modelKeys())->toBe([$flashcardSet->id]);
});

it('only unlinks when a set or a sentence is deleted, and unlinks the lessons, vocabularies and flashcard sets of a deleted set', function () {
    $set = SentenceSet::factory()->hasAttached(Sentence::factory()->count(2), ['sort_order' => 1], 'sentences')->create();
    $lesson = Lesson::factory()->create(['sentence_set_id' => $set->id]);
    $sentence = $set->sentences()->first();

    $sentence->delete();
    expect($set->sentences()->count())->toBe(1);

    $set->delete();

    expect(Sentence::count())->toBe(1)
        ->and(DB::table('sentence_sentence_set')->count())->toBe(0)
        ->and($lesson->fresh()->sentence_set_id)->toBeNull();
});
it('generates the slug of a set from its title', function () {
    expect(SentenceSet::factory()->create(['title' => 'Trái cây', 'slug' => null])->slug)->toBe('trai-cay');
});

it('builds the public urls of the image and audio of a sentence, null when absent', function () {
    Storage::fake('public');

    $with = Sentence::factory()->create(['image' => 'lessons/sentence-images/a.png', 'audio' => 'lessons/sentence-audio/a.mp3']);
    $without = Sentence::factory()->create();

    expect($with->image_url)->toBe(Storage::disk('public')->url('lessons/sentence-images/a.png'))
        ->and($with->audio_url)->toBe(Storage::disk('public')->url('lessons/sentence-audio/a.mp3'))
        ->and($without->image_url)->toBeNull()
        ->and($without->audio_url)->toBeNull();
});
