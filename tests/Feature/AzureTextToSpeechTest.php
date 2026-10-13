<?php

use App\Jobs\GenerateFlashcardAudio;
use App\Models\Flashcard;
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
});

it('retries the job when Azure gives no audio', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(fn () => (new GenerateFlashcardAudio(Flashcard::factory()->create(['title' => 'Apple'])))->handle(app(AzureTextToSpeech::class)))
        ->toThrow(RuntimeException::class);
});
