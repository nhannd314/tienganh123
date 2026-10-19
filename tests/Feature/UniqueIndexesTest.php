<?php

use App\Filament\Resources\Flashcards\Pages\CreateFlashcard;
use App\Filament\Resources\Sentences\Pages\CreateSentence;
use App\Filament\Resources\SentenceSets\Pages\EditSentenceSet;
use App\Filament\Resources\SentenceSets\RelationManagers\SentencesRelationManager;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Models\Flashcard;
use App\Models\Sentence;
use App\Models\SentenceSet;
use App\Models\Tag;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('refuses a second flashcard title, sentence or tag name in the database', function (Closure $duplicate) {
    expect($duplicate)->toThrow(UniqueConstraintViolationException::class);
})->with([
    'flashcard title' => [fn () => Flashcard::factory()->count(2)->create(['title' => 'apple'])],
    'sentence' => [fn () => Sentence::factory()->count(2)->create(['sentence' => 'I eat an apple.'])],
    'tag name' => [fn () => Tag::factory()->count(2)->create(['name' => 'Animals'])],
]);

it('still allows many flashcards without a title', function () {
    Flashcard::factory()->count(2)->create(['title' => null]);

    expect(Flashcard::count())->toBe(2);
});

it('rejects an existing flashcard title, sentence or tag name in the forms', function () {
    Flashcard::factory()->create(['title' => 'apple']);
    Sentence::factory()->create(['sentence' => 'I eat an apple.']);
    Tag::factory()->create(['name' => 'Animals']);

    Livewire::test(CreateFlashcard::class)->fillForm(['title' => 'apple'])->call('create')->assertHasFormErrors(['title' => 'unique']);
    Livewire::test(CreateSentence::class)->fillForm(['sentence' => 'I eat an apple.'])->call('create')->assertHasFormErrors(['sentence' => 'unique']);
    Livewire::test(CreateTag::class)->fillForm(['name' => 'Animals', 'slug' => 'other'])->call('create')->assertHasFormErrors(['name' => 'unique']);
});

it('reuses a sentence already written in another set when it is added by text, and generates nothing for it', function () {
    Queue::fake();
    $existing = Sentence::factory()->create(['sentence' => 'I eat an apple.']);
    $set = SentenceSet::factory()->create();

    Livewire::test(SentencesRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditSentenceSet::class])
        ->callAction(TestAction::make('addSentences')->table(), ['sentence_lines' => 'I eat an apple.']);

    expect(Sentence::where('sentence', 'I eat an apple.')->count())->toBe(1)
        ->and($set->sentences()->pluck('sentences.id')->all())->toBe([$existing->id]);
    Queue::assertNothingPushed();
});
