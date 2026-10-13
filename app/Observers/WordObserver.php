<?php

namespace App\Observers;

use App\Jobs\FetchWordPronunciation;
use App\Models\Word;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class WordObserver implements ShouldHandleEventsAfterCommit
{
    /**
     * Fetch the IPA and audio a new word is missing.
     */
    public function created(Word $word): void
    {
        FetchWordPronunciation::dispatch($word);
    }

    /**
     * Fetch the IPA and audio again when the word itself was edited, as the old ones no longer match it.
     */
    public function updated(Word $word): void
    {
        if ($word->wasChanged('word')) {
            FetchWordPronunciation::dispatch($word, overwrite: true);
        }
    }
}
