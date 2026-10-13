<?php

use App\Filament\Resources\Flashcards\Pages\CreateFlashcard;
use App\Filament\Resources\Flashcards\Pages\EditFlashcard;
use App\Filament\Resources\Flashcards\Pages\ListFlashcards;
use App\Jobs\GenerateFlashcardAudio;
use App\Jobs\GenerateFlashcardImage;
use App\Models\Flashcard;
use App\Models\User;
use App\Models\Word;
use App\Services\OpenAiImageGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('lists flashcards', function () {
    [$first, $second] = Flashcard::factory()->count(2)->create();

    Livewire::test(ListFlashcards::class)
        ->assertCanSeeTableRecords([$first, $second]);
});

it('creates a flashcard linked to a word', function () {
    $word = Word::factory()->create();

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['word_id' => $word->id, 'title' => 'Apple'])
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Flashcard::firstWhere('title', 'Apple'))->word_id->toBe($word->id);
});

it('does not offer the flashcard sets in the form', function () {
    $names = collect(Livewire::test(CreateFlashcard::class)->instance()->getSchema('form')->getComponents())->map(fn ($component) => $component->getName());

    expect($names)->not->toContain('flashcardSets');
});

it('edits a flashcard', function () {
    $flashcard = Flashcard::factory()->create(['title' => 'Old']);

    Livewire::test(EditFlashcard::class, ['record' => $flashcard->getRouteKey()])
        ->fillForm(['title' => 'New'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($flashcard->fresh()->title)->toBe('New');
});

function tinyPng(): string
{
    $image = imagecreatetruecolor(8, 8);

    ob_start();
    imagepng($image);

    return (string) ob_get_clean();
}

it('puts the title first in the form', function () {
    $names = collect(Livewire::test(CreateFlashcard::class)->instance()->getSchema('form')->getComponents())->map(fn ($component) => $component->getName());

    expect($names->first())->toBe('title');
});

it('loads the word spelled like the title when no word is chosen', function () {
    $word = Word::factory()->create(['word' => 'Apple']);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => ' apple '])
        ->assertFormSet(['word_id' => $word->id]);
});

it('keeps the chosen word and leaves the word empty when the title matches none', function () {
    $chosen = Word::factory()->create(['word' => 'Banana']);
    Word::factory()->create(['word' => 'Apple']);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'apple', 'word_id' => $chosen->id])
        ->assertFormSet(['word_id' => $chosen->id]);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'unknown'])
        ->assertFormSet(['word_id' => null]);
});

it('queues the OpenAI image of a new flashcard that has none, and the job stores it', function () {
    Storage::fake('public');
    Queue::fake();
    config(['services.openai.key' => 'openai-key']);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Apple'])
        ->call('create')
        ->assertHasNoFormErrors();

    $flashcard = Flashcard::firstWhere('title', 'Apple');

    expect($flashcard->image)->toBeNull();
    Queue::assertPushed(GenerateFlashcardImage::class, fn (GenerateFlashcardImage $job) => $job->flashcard->is($flashcard));

    Http::fake(['api.openai.com/*' => Http::response(['data' => [['b64_json' => base64_encode(tinyPng())]]])]);
    (new GenerateFlashcardImage($flashcard))->handle(app(OpenAiImageGenerator::class));

    expect($flashcard->fresh()->image)->toStartWith('flashcards/images/')
        ->and(Storage::disk('public')->exists($flashcard->fresh()->image))->toBeTrue();

    Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer openai-key')
        && str_contains($request['prompt'], '"Apple"')
        && $request['background'] === 'transparent');
});

it('does not queue an image when one is uploaded or the key is missing, and the job leaves a card with an image alone', function () {
    Storage::fake('public');
    Queue::fake();
    Http::fake();
    config(['services.openai.key' => 'openai-key']);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Apple', 'image' => UploadedFile::fake()->image('apple.png')])
        ->call('create')
        ->assertHasNoFormErrors();

    config(['services.openai.key' => null]);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Pear'])
        ->call('create')
        ->assertHasNoFormErrors();

    Queue::assertNothingPushed();

    $withImage = Flashcard::factory()->create(['image' => 'flashcards/images/x.png']);
    config(['services.openai.key' => 'openai-key']);
    (new GenerateFlashcardImage($withImage))->handle(app(OpenAiImageGenerator::class));

    Http::assertNothingSent();
});

it('retries the job when OpenAI gives no image', function () {
    config(['services.openai.key' => 'openai-key']);
    Http::fake(['api.openai.com/*' => Http::response([], 500)]);

    expect(fn () => (new GenerateFlashcardImage(Flashcard::factory()->create()))->handle(app(OpenAiImageGenerator::class)))
        ->toThrow(RuntimeException::class);
});

it('queues the Azure audio of a new flashcard that has none, and no audio when a file is uploaded or Azure is not configured', function () {
    Storage::fake('public');
    Queue::fake();
    config(['services.azure_speech.key' => 'speech-key', 'services.azure_speech.region' => 'southeastasia']);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Apple'])
        ->call('create')
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateFlashcardAudio::class, fn (GenerateFlashcardAudio $job) => $job->flashcard->is(Flashcard::firstWhere('title', 'Apple')));

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Pear', 'audio' => UploadedFile::fake()->create('pear.mp3', 10, 'audio/mpeg')])
        ->call('create')
        ->assertHasNoFormErrors();

    config(['services.azure_speech.key' => null]);

    Livewire::test(CreateFlashcard::class)
        ->fillForm(['title' => 'Plum'])
        ->call('create')
        ->assertHasNoFormErrors();

    Queue::assertPushed(GenerateFlashcardAudio::class, 1);
});
