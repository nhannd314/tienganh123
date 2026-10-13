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
    config(['services.oxford_learners.enabled' => true]);

    Http::fake([
        '*/definition/english/apple' => Http::response(oxfordEntry('/ˈæpl/', '/ˈæpəl/')),
        '*/definition/english/*' => Http::response('Word not found', 404),
        '*/media/uk/*' => Http::response('uk-bytes'),
        '*/media/us/*' => Http::response('us-bytes'),
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

    it('stores the audio and fills the IPA of both accents', function () {
        $word = Word::factory()->create(['word' => 'apple', 'slug' => 'apple', 'ipa_us' => null, 'ipa_uk' => null]);
        fakeOxford();

        FetchWordPronunciation::dispatchSync($word);

        expect(Storage::disk('public')->get('audio/us/apple.mp3'))->toBe('us-bytes')
            ->and(Storage::disk('public')->get('audio/uk/apple.mp3'))->toBe('uk-bytes')
            ->and($word->fresh())->ipa_us->toBe('/ˈæpəl/')->ipa_uk->toBe('/ˈæpl/');
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

        expect(Storage::disk('public')->get('audio/us/apple.mp3'))->toBe('us-bytes')
            ->and($word->fresh()->ipa_us)->toBe('/ˈæpəl/');
    });

    it('does nothing when the dictionary has no entry for the word', function () {
        $word = Word::factory()->create(['word' => 'zzzqqx', 'slug' => 'zzzqqx', 'ipa_us' => null]);
        fakeOxford();

        FetchWordPronunciation::dispatchSync($word);

        Storage::disk('public')->assertMissing('audio/us/zzzqqx.mp3');
        expect($word->fresh()->ipa_us)->toBeNull();
    });

    it('does nothing when the lookup is disabled', function () {
        $word = Word::factory()->create();
        Http::fake();

        FetchWordPronunciation::dispatchSync($word);

        Http::assertNothingSent();
    });
});
