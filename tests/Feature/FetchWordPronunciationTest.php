<?php

use App\Jobs\FetchWordPronunciation;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function oxfordEntry(string $ukIpa = '/ˈæpl/', string $usIpa = '/ˈæpl/'): string
{
    return <<<HTML
        <div class="webtop"><div class="phonetics">
        <div class="phons_br" geo="br"><div class="sound pron-uk" data-src-mp3="https://www.oxfordlearnersdictionaries.com/media/uk/apple__gb_5.mp3"> </div><span class="phon">{$ukIpa}</span></div>
        <div class="phons_n_am" geo="n_am"><div class="sound pron-us" data-src-mp3="https://www.oxfordlearnersdictionaries.com/media/us/apple__us_1.mp3"> </div><span class="phon">{$usIpa}</span></div>
        </div></div>
        HTML;
}

function fakeOxford(): void
{
    config([
        'services.oxford_learners.enabled' => true,
        'services.azure_speech' => ['key' => 'speech-key', 'region' => 'southeastasia', 'endpoint' => null, 'voice' => 'en-US-AnaNeural', 'voice_style' => null],
    ]);

    Http::fake([
        '*/definition/english/apple' => Http::response(oxfordEntry('/ˈæpl/', '/ˈæpəl/')),
        '*/definition/english/*' => Http::response('Word not found', 404),
        '*.tts.speech.microsoft.com/*' => Http::response('azure-bytes'),
    ]);
}

describe('observer', function () {
    it('queues the fetch when a word is created', function () {
        Queue::fake();

        $word = Word::factory()->create();

        Queue::assertPushed(FetchWordPronunciation::class, fn ($job) => $job->word->is($word) && ! $job->overwrite);
    });

    it('queues an overwriting fetch only when the word itself changes', function () {
        $word = Word::factory()->create(['word' => 'apple']);
        Queue::fake();

        $word->update(['image' => 'words/apple.jpg']);
        Queue::assertNothingPushed();

        $word->update(['word' => 'apples']);
        Queue::assertPushed(FetchWordPronunciation::class, fn ($job) => $job->overwrite);
    });
});

describe('job', function () {
    beforeEach(function () {
        Storage::fake('public');
    });

    it('fills the IPA from the dictionary and reads the word with Azure in both accents, with an adult voice', function () {
        $word = Word::factory()->create(['word' => 'apple', 'slug' => 'apple', 'ipa_us' => null, 'ipa_uk' => null]);
        fakeOxford();

        FetchWordPronunciation::dispatchSync($word);

        expect(Storage::disk('public')->get('audio/us/apple.mp3'))->toBe('azure-bytes')
            ->and(Storage::disk('public')->get('audio/uk/apple.mp3'))->toBe('azure-bytes')
            ->and($word->fresh())->ipa_us->toBe('/ˈæpəl/')->ipa_uk->toBe('/ˈæpl/');

        Http::assertSent(fn ($request) => str_contains($request->url(), 'tts.speech')
            && preg_match('/<voice name="en-US-(Jenny|Guy|Aria|Davis|Jane|Jason|Sara|Tony)Neural">/', $request->body()) === 1
            && str_contains($request->body(), 'xml:lang="en-US"'));
        Http::assertSent(fn ($request) => str_contains($request->url(), 'tts.speech')
            && preg_match('/<voice name="en-GB-(Sonia|Ryan|Libby|Thomas)Neural">/', $request->body()) === 1
            && str_contains($request->body(), 'xml:lang="en-GB"'));
    });

    it('keeps the audio and IPA already there unless told to overwrite', function () {
        $word = Word::factory()->create(['word' => 'apple', 'slug' => 'apple', 'ipa_us' => '/a/', 'ipa_uk' => '/b/']);
        Storage::disk('public')->put('audio/us/apple.mp3', 'old');
        Storage::disk('public')->put('audio/uk/apple.mp3', 'old');
        fakeOxford();

        FetchWordPronunciation::dispatchSync($word);

        expect(Storage::disk('public')->get('audio/us/apple.mp3'))->toBe('old')
            ->and($word->fresh()->ipa_us)->toBe('/a/');

        FetchWordPronunciation::dispatchSync($word, overwrite: true);

        expect(Storage::disk('public')->get('audio/us/apple.mp3'))->toBe('azure-bytes')
            ->and($word->fresh()->ipa_us)->toBe('/ˈæpəl/');
    });

    it('still reads the word when the dictionary has no entry for it', function () {
        $word = Word::factory()->create(['word' => 'zzzqqx', 'slug' => 'zzzqqx', 'ipa_us' => null]);
        fakeOxford();

        FetchWordPronunciation::dispatchSync($word);

        expect(Storage::disk('public')->get('audio/us/zzzqqx.mp3'))->toBe('azure-bytes')
            ->and($word->fresh()->ipa_us)->toBeNull();
    });

    it('skips the IPA when the lookup is disabled and the audio when Azure is not configured', function () {
        $word = Word::factory()->create();
        config(['services.oxford_learners.enabled' => false, 'services.azure_speech.key' => null]);
        Http::fake();

        FetchWordPronunciation::dispatchSync($word);

        Http::assertNothingSent();
    });

    it('retries the job when Azure gives no audio', function () {
        $word = Word::factory()->create(['word' => 'apple', 'slug' => 'apple']);
        Http::fake(['*.tts.speech.microsoft.com/*' => Http::response('', 500)]);
        fakeOxford();

        expect(fn () => FetchWordPronunciation::dispatchSync($word))->toThrow(RuntimeException::class);
    });
});

it('picks the US or the UK pronunciation at random among the saved files', function () {
    Storage::fake('public');
    $word = Word::factory()->create(['word' => 'apple']);

    expect($word->random_audio_url)->toBeNull();

    Storage::disk('public')->put("audio/us/{$word->slug}.mp3", 'US');

    expect($word->random_audio_url)->toBe($word->audio_us_url);

    Storage::disk('public')->put("audio/uk/{$word->slug}.mp3", 'UK');

    $picked = collect(range(1, 40))->map(fn (): ?string => $word->random_audio_url)->unique();

    expect($picked->sort()->values()->all())->toBe(collect([$word->audio_us_url, $word->audio_uk_url])->sort()->values()->all());
});
