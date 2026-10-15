<?php

use App\Jobs\GenerateFlashcardAudio;
use App\Jobs\GenerateLessonSentenceAudio;
use App\Models\Flashcard;
use App\Models\Lesson;
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

it('stores the audio of the lesson sentences that have none, and keeps an edit made while Azure was reading', function () {
    Storage::fake('public');
    $lesson = Lesson::factory()->create(['example_sentences' => [
        ['sentence' => 'I eat an `apple`.', 'translation' => 'Tôi ăn một quả táo.'],
        ['sentence' => 'Hello.', 'audio' => 'lessons/sentence-audio/hello.mp3'],
    ]]);
    Http::fake(['*' => function () use ($lesson) {
        $lesson->update(['example_sentences' => [...$lesson->example_sentences, ['sentence' => 'Added meanwhile.']]]);

        return Http::response('MP3-BYTES');
    }]);

    (new GenerateLessonSentenceAudio($lesson))->handle(app(AzureTextToSpeech::class));

    $sentences = $lesson->fresh()->example_sentences;

    expect($sentences)->toHaveCount(3)
        ->and($sentences[0]['translation'])->toBe('Tôi ăn một quả táo.')
        ->and($sentences[0]['audio'])->toStartWith('lessons/sentence-audio/')->toEndWith('.mp3')
        ->and(Storage::disk('public')->get($sentences[0]['audio']))->toBe('MP3-BYTES')
        ->and($sentences[1]['audio'])->toBe('lessons/sentence-audio/hello.mp3')
        ->and($sentences[2])->toBe(['sentence' => 'Added meanwhile.']);

    Http::assertSentCount(1);
    Http::assertSent(fn ($request) => str_contains($request->body(), '>I eat an apple.</prosody>')
        && preg_match('/<voice name="en-US-(Jenny|Guy)Neural">/', $request->body()) === 1);
});

it('retries the lesson sentence job when Azure gives no audio', function () {
    Http::fake(['*' => Http::response('', 500)]);
    $lesson = Lesson::factory()->create(['example_sentences' => [['sentence' => 'Hello.']]]);

    expect(fn () => (new GenerateLessonSentenceAudio($lesson))->handle(app(AzureTextToSpeech::class)))
        ->toThrow(RuntimeException::class);
});

it('retries the job when Azure gives no audio', function () {
    Http::fake(['*' => Http::response('', 500)]);

    expect(fn () => (new GenerateFlashcardAudio(Flashcard::factory()->create(['title' => 'Apple'])))->handle(app(AzureTextToSpeech::class)))
        ->toThrow(RuntimeException::class);
});
