<?php

use App\Filament\Resources\FlashcardSets\Pages\EditFlashcardSet;
use App\Filament\Resources\FlashcardSets\RelationManagers\FlashcardsRelationManager;
use App\Models\Flashcard;
use App\Models\FlashcardSet;
use App\Models\User;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lets one flashcard belong to several sets', function () {
    [$first, $second] = FlashcardSet::factory()->count(2)->create();
    $flashcard = Flashcard::factory()->create();

    $manager = fn (FlashcardSet $set) => Livewire::test(FlashcardsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditFlashcardSet::class]);

    $manager($first)->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $flashcard->id])->assertHasNoFormErrors();
    $manager($second)->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $flashcard->id])->assertHasNoFormErrors();

    expect($flashcard->flashcardSets()->count())->toBe(2)
        ->and($first->flashcards()->pluck('flashcards.id')->all())->toBe([$flashcard->id]);
});

it('lists and reorders the flashcards of the set', function () {
    $set = FlashcardSet::factory()->create();
    [$first, $second] = Flashcard::factory()->count(2)->create();
    $set->flashcards()->attach([$first->id => ['sort_order' => 1], $second->id => ['sort_order' => 2]]);

    Livewire::test(FlashcardsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditFlashcardSet::class])
        ->assertCanSeeTableRecords([$first, $second], inOrder: true)
        ->call('reorderTable', [$second->id, $first->id]);

    expect($set->flashcards()->pluck('flashcards.id')->all())->toBe([$second->id, $first->id]);
});

it('creates a flashcard straight into the set', function () {
    $set = FlashcardSet::factory()->create();

    Livewire::test(FlashcardsRelationManager::class, ['ownerRecord' => $set, 'pageClass' => EditFlashcardSet::class])
        ->callAction(TestAction::make('create')->table(), ['title' => 'Apple'])
        ->assertHasNoFormErrors();

    expect($set->flashcards()->pluck('title')->all())->toBe(['Apple']);
});
