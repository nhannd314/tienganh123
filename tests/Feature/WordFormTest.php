<?php

use App\Filament\Resources\Words\Pages\CreateWord;
use App\Filament\Resources\Words\Pages\EditWord;
use App\Models\User;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('creates a word with meanings, examples and a generated slug', function () {
    Livewire::test(CreateWord::class)
        ->fillForm([
            'word' => 'Apple',
            'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']],
            'examples' => [['sentence' => 'I eat an apple.', 'translation' => 'Tôi ăn một quả táo.']],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $word = Word::firstWhere('word', 'Apple');

    expect($word->slug)->toBe('apple')
        ->and($word->meanings)->toHaveCount(1)
        ->and($word->meanings[0])->toMatchArray(['pos' => 'noun', 'meaning' => 'quả táo'])
        ->and($word->examples[0]['sentence'])->toBe('I eat an apple.');
});

it('requires at least one meaning with a part of speech', function () {
    Livewire::test(CreateWord::class)
        ->fillForm(['word' => 'Apple', 'meanings' => []])
        ->call('create')
        ->assertHasFormErrors(['meanings']);

    Livewire::test(CreateWord::class)
        ->fillForm(['word' => 'Apple', 'meanings' => [['pos' => null, 'meaning' => 'quả táo']]])
        ->call('create')
        ->assertHasFormErrors(['meanings.0.pos' => 'required']);
});

it('rejects a word that already exists', function () {
    Word::factory()->create(['word' => 'apple', 'slug' => 'apple']);

    Livewire::test(CreateWord::class)
        ->fillForm(['word' => 'apple', 'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']]])
        ->call('create')
        ->assertHasFormErrors(['word' => 'unique']);
});

it('allows keeping the own word when editing', function () {
    $word = Word::factory()->create(['word' => 'apple']);

    Livewire::test(EditWord::class, ['record' => $word->getRouteKey()])
        ->fillForm(['ipa_us' => '/æp/'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($word->refresh()->ipa_us)->toBe('/æp/');
});

it('stores the uploaded image on the public disk', function () {
    Storage::fake('public');

    Livewire::test(CreateWord::class)
        ->fillForm([
            'word' => 'Apple',
            'meanings' => [['pos' => 'noun', 'meaning' => 'quả táo']],
            'image' => UploadedFile::fake()->image('apple.png'),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $word = Word::firstWhere('word', 'Apple');

    expect($word->image)->toStartWith('words/');
    Storage::disk('public')->assertExists($word->image);
});
