<?php

use App\Filament\Resources\Vocabularies\Actions\BulkAddWordsAction;
use App\Filament\Resources\Vocabularies\Pages\EditVocabulary;
use App\Filament\Resources\Vocabularies\RelationManagers\WordsRelationManager;
use App\Models\User;
use App\Models\Vocabulary;
use App\Models\Word;
use Filament\Actions\AttachAction;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('attaches a word to the vocabulary', function () {
    $vocabulary = Vocabulary::factory()->create();
    $word = Word::factory()->create();

    Livewire::test(WordsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->callAction(TestAction::make(AttachAction::class)->table(), ['recordId' => $word->id])
        ->assertHasNoFormErrors();

    expect($vocabulary->words()->pluck('words.id')->all())->toBe([$word->id]);
});

it('parses one word per line with its parts of speech and comma separated meanings', function () {
    expect(BulkAddWordsAction::parse("apple (noun) quả táo, táo\n\n  work (noun) công việc (verb) làm việc  \nice cream (noun) kem, kem, Kem\nwater"))->toBe([
        ['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo']]],
        ['word' => 'work', 'meanings' => [['pos' => 'noun', 'meaning' => 'công việc'], ['pos' => 'verb', 'meaning' => 'làm việc']]],
        ['word' => 'ice cream', 'meanings' => [['pos' => 'noun', 'meaning' => 'kem']]],
        ['word' => 'water', 'meanings' => []],
    ]);
});

it('bulk adds new words and adds only the missing meanings to existing words', function () {
    $vocabulary = Vocabulary::factory()->create();
    $work = Word::factory()->create(['word' => 'Work', 'meanings' => [['pos' => 'noun', 'meaning' => 'công việc']]]);

    Livewire::test(WordsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->callAction(TestAction::make('bulkAddWords')->table(), [
            'words' => "apple (noun) quả táo, táo\nwork (noun) Công việc, việc làm (verb) làm việc",
        ])
        ->assertHasNoFormErrors();

    expect(Word::where('word', 'apple')->count())->toBe(1)
        ->and(Word::firstWhere('word', 'apple')->meanings)->toBe([['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo']])
        ->and(Word::whereRaw('lower(word) = ?', ['work'])->count())->toBe(1)
        ->and($work->fresh()->meanings)->toBe([
            ['pos' => 'noun', 'meaning' => 'công việc'],
            ['pos' => 'noun', 'meaning' => 'việc làm'],
            ['pos' => 'verb', 'meaning' => 'làm việc'],
        ])
        ->and($vocabulary->words()->pluck('word')->all())->toBe(['apple', 'Work']);
});

it('does not attach a word twice or touch words that already have every meaning', function () {
    $vocabulary = Vocabulary::factory()->create();
    $apple = Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']]]);
    $vocabulary->words()->attach($apple, ['sort_order' => 5]);

    $result = BulkAddWordsAction::apply($vocabulary, BulkAddWordsAction::parse('apple (noun) quả táo'));

    expect($result)->toBe(['created' => 0, 'updated' => 0, 'attached' => 0])
        ->and($vocabulary->words()->count())->toBe(1)
        ->and($vocabulary->words()->first()->pivot->sort_order)->toBe(5);
});

it('lists and reorders the words of the vocabulary', function () {
    $vocabulary = Vocabulary::factory()->create();
    [$first, $second] = Word::factory()->count(2)->create();
    $vocabulary->words()->attach([$first->id => ['sort_order' => 1], $second->id => ['sort_order' => 2]]);

    Livewire::test(WordsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->assertCanSeeTableRecords([$first, $second], inOrder: true)
        ->call('reorderTable', [$second->id, $first->id]);

    expect($vocabulary->words()->pluck('words.id')->all())->toBe([$second->id, $first->id]);
});
