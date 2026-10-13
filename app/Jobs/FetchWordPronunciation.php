<?php

namespace App\Jobs;

use App\Models\Word;
use App\Services\OxfordLearnersPronunciation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;

class FetchWordPronunciation implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /**
     * @param  bool  $overwrite  Replace the IPA and the audio already there; otherwise only the missing ones are fetched.
     */
    public function __construct(public Word $word, public bool $overwrite = false) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Fetch the IPA and the MP3 of each accent from Oxford Learner's Dictionaries.
     * Does nothing when the lookup is disabled or the dictionary has no entry for the word.
     */
    public function handle(OxfordLearnersPronunciation $dictionary): void
    {
        if (! $dictionary->isEnabled()) {
            return;
        }

        $pronunciations = $dictionary->lookup($this->word->word);

        if ($pronunciations === null) {
            return;
        }

        $disk = Storage::disk('public');

        foreach ($pronunciations as $accent => $pronunciation) {
            $path = "audio/{$accent}/{$this->word->slug}.mp3";
            $ipaColumn = "ipa_{$accent}";

            if ($pronunciation['audio'] !== null && ($this->overwrite || ! $disk->exists($path))) {
                $disk->put($path, $dictionary->download($pronunciation['audio']));
            }

            if ($pronunciation['ipa'] !== null && ($this->overwrite || blank($this->word->{$ipaColumn}))) {
                $this->word->{$ipaColumn} = $pronunciation['ipa'];
            }
        }

        $this->word->save();
    }
}
