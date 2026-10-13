<?php

use App\Jobs\FetchWordPronunciation;
use App\Models\Word;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    Storage::fake('public');
    Queue::fake();
});

function completeWord(string $word): Word
{
    $word = Word::factory()->create(['word' => $word, 'slug' => $word, 'ipa_us' => '/a/', 'ipa_uk' => '/b/']);
    Storage::disk('public')->put("audio/us/{$word->slug}.mp3", 'x');
    Storage::disk('public')->put("audio/uk/{$word->slug}.mp3", 'x');

    return $word;
}

it('queues only the words missing an IPA or an audio file', function () {
    completeWord('apple');
    $noIpa = Word::factory()->create(['word' => 'pear', 'slug' => 'pear', 'ipa_us' => null, 'ipa_uk' => '/b/']);
    $noAudio = Word::factory()->create(['word' => 'plum', 'slug' => 'plum', 'ipa_us' => '/a/', 'ipa_uk' => '/b/']);
    Queue::fake();

    $this->artisan('words:fetch-pronunciation')->expectsOutput('Queued 2 word(s).')->assertSuccessful();

    Queue::assertPushed(FetchWordPronunciation::class, 2);
    Queue::assertPushed(FetchWordPronunciation::class, fn ($job) => $job->word->is($noIpa) && ! $job->overwrite);
    Queue::assertPushed(FetchWordPronunciation::class, fn ($job) => $job->word->is($noAudio));
});

it('queues every word with --overwrite, one delay apart', function () {
    completeWord('apple');
    completeWord('pear');
    Queue::fake();

    $this->artisan('words:fetch-pronunciation', ['--overwrite' => true, '--delay' => 5])->assertSuccessful();

    Queue::assertPushed(FetchWordPronunciation::class, 2);
    Queue::assertPushed(FetchWordPronunciation::class, fn ($job) => $job->overwrite && $job->delay->diffInSeconds(now(), true) > 0);
});
