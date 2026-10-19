<?php

use App\Jobs\GenerateFlashcardAudio;
use App\Jobs\GenerateSentenceAudio;
use App\Models\Flashcard;
use App\Models\Sentence;
use App\Services\AzureTextToSpeech;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    config(['services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null, 'voice' => 'en-US-AnaNeural', 'voice_style' => null]]);
});

it('reads the text with the configured child voice, a little slower, and returns the MP3', function () {
    Http::fake(['*' => Http::response('MP3-BYTES')]);

    expect(app(AzureTextToSpeech::class)->synthesize('Tom & "Jerry"'))->toBe('MP3-BYTES');

    Http::assertSent(fn ($request) => $request->url() === 'https://southeastasia.tts.speech.microsoft.com/cognitiveservices/v1'
        && $request->hasHeader('Ocp-Apim-Subscription-Key', 'speech-key')
        && $request->hasHeader('X-Microsoft-OutputFormat', 'audio-24khz-48kbitrate-mono-mp3')
        && $request->hasHeader('Content-Type', 'application/ssml+xml')
        && str_contains($request->body(), '<voice name="en-US-AnaNeural">')
        && str_contains($request->body(), '<prosody rate="-10%">Tom &amp; &quot;Jerry&quot;</prosody>')
        && ! str_contains($request->body(), 'express-as'));
});

it('wraps the speech in the configured style, e.g. a cheerful woman', function () {
    config(['services.azure_speech.voice' => 'en-US-JennyNeural', 'services.azure_speech.voice_style' => 'cheerful']);
    Http::fake(['*' => Http::response('MP3-BYTES')]);

    app(AzureTextToSpeech::class)->synthesize('apple');

    Http::assertSent(fn ($request) => str_contains($request->body(), '<voice name="en-US-JennyNeural"><mstts:express-as style="cheerful"><prosody rate="-10%">apple</prosody></mstts:express-as></voice>'));
});

it('returns null without calling Azure when it is not configured or there is nothing to read', function () {
    Http::fake();

    expect(app(AzureTextToSpeech::class)->synthesize(''))->toBeNull();

    config(['services.azure_speech.key' => null]);

    expect(app(AzureTextToSpeech::class)->isConfigured())->toBeFalse()
        ->and(app(AzureTextToSpeech::class)->synthesize('apple'))->toBeNull();

    Http::assertNothingSent();
});

it('returns null and logs the reason when Azure answers with an error', function () {
    Log::spy();
    Http::fake(['*' => Http::response('Invalid voice', 400)]);

    expect(app(AzureTextToSpeech::class)->synthesize('apple'))->toBeNull();

    Log::shouldHaveReceived('warning')->once();
});

it('stores the audio of a flashcard that has none, and leaves one that has audio alone', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('MP3-BYTES')]);
    $flashcard = Flashcard::factory()->create(['title' => 'Apple']);

    (new GenerateFlashcardAudio($flashcard))->handle(app(AzureTextToSpeech::class));

    $stored = $flashcard->fresh()->audio;

    expect($stored)->toStartWith('flashcards/audio/')->toEndWith('.mp3')
        ->and(Storage::disk('public')->get($stored))->toBe('MP3-BYTES');

    (new GenerateFlashcardAudio($flashcard))->handle(app(AzureTextToSpeech::class));

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request->body(), '<voice name="'.app(AzureTextToSpeech::class)->flashcardVoice($flashcard->id).'">'));
});

it('stores the audio of a sentence that has none, read without its backticks, and leaves one that has audio alone', function () {
    Storage::fake('public');
    Http::fake(['*' => Http::response('MP3-BYTES')]);
    $sentence = Sentence::factory()->create(['sentence' => 'I eat an `apple`.', 'audio' => null]);
    $withAudio = Sentence::factory()->create(['audio' => 'lessons/sentence-audio/hello.mp3']);

    (new GenerateSentenceAudio($sentence))->handle(app(AzureTextToSpeech::class));
    (new GenerateSentenceAudio($withAudio))->handle(app(AzureTextToSpeech::class));

    $stored = $sentence->fresh()->audio;

    expect($stored)->toStartWith('lessons/sentence-audio/')->toEndWith('.mp3')
        ->and(Storage::disk('public')->get($stored))->toBe('MP3-BYTES')
        ->and($withAudio->fresh()->audio)->toBe('lessons/sentence-audio/hello.mp3');

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request->body(), '>I eat an apple.</prosody>')
        && str_contains($request->body(), '<voice name="'.app(AzureTextToSpeech::class)->sentenceVoice($sentence->id).'">'));
});

it('retries the sentence job when Azure gives no audio', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(fn () => (new GenerateSentenceAudio(Sentence::factory()->create()))->handle(app(AzureTextToSpeech::class)))
        ->toThrow(RuntimeException::class);
});

it('retries the job when Azure gives no audio', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(fn () => (new GenerateFlashcardAudio(Flashcard::factory()->create(['title' => 'Apple'])))->handle(app(AzureTextToSpeech::class)))
        ->toThrow(RuntimeException::class);
});

it('changes the voice from one record to the next so the audio is not dull', function () {
    $speech = app(AzureTextToSpeech::class);

    expect(collect(range(0, 7))->map(fn (int $id): string => $speech->wordVoice('us', $id))->unique())->toHaveCount(8)
        ->and($speech->wordVoice('us', 0))->toBe('en-US-JennyNeural')
        ->and($speech->wordVoice('us', 1))->toBe('en-US-GuyNeural')
        ->and($speech->wordVoice('uk', 1))->toBe('en-GB-RyanNeural')
        ->and($speech->wordVoice('us', 8))->toBe($speech->wordVoice('us', 0))
        ->and(collect(range(0, 3))->map(fn (int $id): string => $speech->flashcardVoice($id))->all())
        ->toBe(['en-US-JennyNeural', 'en-US-AnaNeural', 'en-US-JennyNeural', 'en-US-AnaNeural'])
        ->and(collect(range(0, 5))->map(fn (int $id): string => $speech->sentenceVoice($id))->all())
        ->toBe(['en-US-JennyNeural', 'en-US-GuyNeural', 'en-US-AnaNeural', 'en-US-JennyNeural', 'en-US-GuyNeural', 'en-US-AnaNeural']);
});
