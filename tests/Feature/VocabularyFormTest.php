<?php

use App\Filament\Resources\Vocabularies\Actions\BulkAddWordsAction;
use App\Filament\Resources\Vocabularies\Pages\EditVocabulary;
use App\Filament\Resources\Vocabularies\RelationManagers\WordsRelationManager;
use App\Models\SentenceSet;
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
    expect(BulkAddWordsAction::parse("apple [noun] quả táo, táo\n\n  work [noun] công việc [verb] làm việc  \nice cream [noun] kem, kem, Kem\nwater"))->toBe([
        ['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo']], 'examples' => []],
        ['word' => 'work', 'meanings' => [['pos' => 'noun', 'meaning' => 'công việc'], ['pos' => 'verb', 'meaning' => 'làm việc']], 'examples' => []],
        ['word' => 'ice cream', 'meanings' => [['pos' => 'noun', 'meaning' => 'kem']], 'examples' => []],
        ['word' => 'water', 'meanings' => [], 'examples' => []],
    ]);
});

it('keeps the round brackets of a meaning, which are only a note, even with a comma inside', function () {
    expect(BulkAddWordsAction::parse('book [verb] đặt trước (vé, phòng), đặt chỗ [noun] quyển sách (to)'))->toBe([
        ['word' => 'book', 'meanings' => [['pos' => 'verb', 'meaning' => 'đặt trước (vé, phòng)'], ['pos' => 'verb', 'meaning' => 'đặt chỗ'], ['pos' => 'noun', 'meaning' => 'quyển sách (to)']], 'examples' => []],
    ]);
});

it('parses the examples after a bar, each with its translation after an equals sign', function () {
    expect(BulkAddWordsAction::parse('apple [noun] quả táo (trái cây) | I have an apple = Tôi có một quả táo | She likes apples'))->toBe([
        [
            'word' => 'apple',
            'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo (trái cây)']],
            'examples' => [
                ['sentence' => 'I have an apple', 'translation' => 'Tôi có một quả táo'],
                ['sentence' => 'She likes apples', 'translation' => null],
            ],
        ],
    ]);
});

it('bulk adds new words and adds only the missing meanings to existing words', function () {
    $vocabulary = Vocabulary::factory()->create();
    $work = Word::factory()->create(['word' => 'Work', 'meanings' => [['pos' => 'noun', 'meaning' => 'công việc']]]);

    Livewire::test(WordsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->callAction(TestAction::make('bulkAddWords')->table(), [
            'words' => "apple [noun] quả táo, táo | I have an apple = Tôi có một quả táo\nwork [noun] Công việc, việc làm [verb] làm việc",
        ])
        ->assertHasNoFormErrors();

    expect(Word::where('word', 'apple')->count())->toBe(1)
        ->and(Word::firstWhere('word', 'apple')->meanings)->toBe([['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo']])
        ->and(Word::firstWhere('word', 'apple')->examples)->toBe([['sentence' => 'I have an apple', 'translation' => 'Tôi có một quả táo', 'audio' => null]])
        ->and(Word::whereRaw('lower(word) = ?', ['work'])->count())->toBe(1)
        ->and($work->fresh()->meanings)->toBe([
            ['pos' => 'noun', 'meaning' => 'công việc'],
            ['pos' => 'noun', 'meaning' => 'việc làm'],
            ['pos' => 'verb', 'meaning' => 'làm việc'],
        ])
        ->and($vocabulary->words()->pluck('word')->all())->toBe(['apple', 'Work']);
});

it('adds only the missing examples to an existing word', function () {
    $vocabulary = Vocabulary::factory()->create();
    $apple = Word::factory()->create([
        'word' => 'apple',
        'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']],
        'examples' => [['sentence' => 'I have an apple', 'translation' => 'Tôi có một quả táo', 'audio' => 'audio/examples/apple-1.mp3']],
    ]);

    $result = BulkAddWordsAction::apply($vocabulary, BulkAddWordsAction::parse('apple | i have an apple = x | She eats an apple = Cô ấy ăn táo'));

    expect($result)->toBe(['created' => 0, 'updated' => 1, 'attached' => 1])
        ->and($apple->fresh()->examples)->toBe([
            ['sentence' => 'I have an apple', 'translation' => 'Tôi có một quả táo', 'audio' => 'audio/examples/apple-1.mp3'],
            ['sentence' => 'She eats an apple', 'translation' => 'Cô ấy ăn táo', 'audio' => null],
        ]);
});

it('does not attach a word twice or touch words that already have every meaning', function () {
    $vocabulary = Vocabulary::factory()->create();
    $apple = Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']]]);
    $vocabulary->words()->attach($apple, ['sort_order' => 5]);

    $result = BulkAddWordsAction::apply($vocabulary, BulkAddWordsAction::parse('apple [noun] quả táo'));

    expect($result)->toBe(['created' => 0, 'updated' => 0, 'attached' => 0])
        ->and($vocabulary->words()->count())->toBe(1)
        ->and($vocabulary->words()->first()->pivot->sort_order)->toBe(5);
});

it('loads a word that is already stored, with its meanings but not its examples, in place of the word typed alone on a line', function () {
    Word::factory()->create([
        'word' => 'Apple',
        'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo'], ['pos' => 'verb', 'meaning' => 'ăn táo']],
        'examples' => [['sentence' => 'I have an apple', 'translation' => 'Tôi có một quả táo', 'audio' => null]],
    ]);

    expect(BulkAddWordsAction::loadKnownWords("apple\nbanana\nwork [noun] công việc"))
        ->toBe('Apple [n] quả táo, táo [v] ăn táo
banana
work [noun] công việc')
        ->and(BulkAddWordsAction::loadKnownWords('banana'))->toBe('banana');
});

it('fills the box with the stored word when the box is left after typing only the word', function () {
    $vocabulary = Vocabulary::factory()->create();
    Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']], 'examples' => []]);

    Livewire::test(WordsRelationManager::class, ['ownerRecord' => $vocabulary, 'pageClass' => EditVocabulary::class])
        ->mountAction(TestAction::make('bulkAddWords')->table())
        ->fillForm(['words' => 'apple'])
        ->assertSchemaStateSet(['words' => 'apple [n] quả táo']);
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

it('links a vocabulary to a sentence set', function () {
    $vocabulary = Vocabulary::factory()->create();
    $sentenceSet = SentenceSet::factory()->create();

    Livewire::test(EditVocabulary::class, ['record' => $vocabulary->getRouteKey()])
        ->fillForm(['sentence_set_id' => $sentenceSet->id])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($vocabulary->refresh()->sentenceSet->is($sentenceSet))->toBeTrue();
});

it('stores an abbreviated part of speech in full, and keeps a full or unknown one as typed', function () {
    expect(Word::expandPartOfSpeech('n'))->toBe('noun')
        ->and(Word::expandPartOfSpeech('V'))->toBe('verb')
        ->and(Word::expandPartOfSpeech('a'))->toBe('adjective')
        ->and(Word::expandPartOfSpeech('adj'))->toBe('adjective')
        ->and(Word::expandPartOfSpeech('adv'))->toBe('adverb')
        ->and(Word::expandPartOfSpeech('Noun'))->toBe('noun')
        ->and(Word::expandPartOfSpeech('phrasal verb'))->toBe('phrasal verb');

    $entries = BulkAddWordsAction::parse("apple [n] quả táo, táo [v] ăn táo\nslow [a] chậm [adv] một cách chậm chạp\nwork [noun] công việc [N] việc làm");

    expect($entries[0]['meanings'])->toBe([['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo'], ['pos' => 'verb', 'meaning' => 'ăn táo']])
        ->and(array_column($entries[1]['meanings'], 'pos'))->toBe(['adjective', 'adverb'])
        ->and(array_column($entries[2]['meanings'], 'pos'))->toBe(['noun', 'noun']);

    $vocabulary = Vocabulary::factory()->create();
    BulkAddWordsAction::apply($vocabulary, $entries);

    expect(Word::firstWhere('word', 'apple')->meanings)->toBe([['pos' => 'noun', 'meaning' => 'quả táo'], ['pos' => 'noun', 'meaning' => 'táo'], ['pos' => 'verb', 'meaning' => 'ăn táo']]);
});

it('does not add a second meaning when the same one is typed again with the abbreviated part of speech', function () {
    $vocabulary = Vocabulary::factory()->create();
    $word = Word::factory()->create(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']], 'examples' => []]);

    $result = BulkAddWordsAction::apply($vocabulary, BulkAddWordsAction::parse('apple [n] quả táo'));

    expect($result['updated'])->toBe(0)
        ->and($word->fresh()->meanings)->toHaveCount(1);
});
