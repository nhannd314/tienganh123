<?php

namespace App\Jobs;

use App\Models\Word;
use App\Services\AzureTextToSpeech;
use App\Services\OxfordLearnersPronunciation;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

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
     * Fetch the IPA of each accent from Oxford Learner's Dictionaries, and read the word aloud in each accent with Azure.
     * The IPA is skipped when the lookup is disabled or the dictionary has no entry for the word; the audio, when Azure
     * is not configured. An accent Azure fails to read is retried.
     */
    public function handle(OxfordLearnersPronunciation $dictionary, AzureTextToSpeech $speech): void
    {
        $this->fetchIpa($dictionary);
        $this->generateAudio($speech);
    }

    private function fetchIpa(OxfordLearnersPronunciation $dictionary): void
    {
        if (! $dictionary->isEnabled()) {
            return;
        }

        $pronunciations = $dictionary->lookup($this->word->word);

        if ($pronunciations === null) {
            return;
        }

        foreach ($pronunciations as $accent => $pronunciation) {
            $ipaColumn = "ipa_{$accent}";

            if ($pronunciation['ipa'] !== null && ($this->overwrite || blank($this->word->{$ipaColumn}))) {
                $this->word->{$ipaColumn} = $pronunciation['ipa'];
            }
        }

        $this->word->save();
    }

    private function generateAudio(AzureTextToSpeech $speech): void
    {
        if (! $speech->isConfigured()) {
            return;
        }

        $disk = Storage::disk('public');
        $failed = false;

        foreach (['us', 'uk'] as $accent) {
            $path = "audio/{$accent}/{$this->word->slug}.mp3";

            if (! $this->overwrite && $disk->exists($path)) {
                continue;
            }

            $mp3 = $speech->synthesize($this->word->word, $speech->wordVoice($accent));

            if ($mp3 === null) {
                $failed = true;

                continue;
            }

            $disk->put($path, $mp3);
        }

        if ($failed) {
            throw new RuntimeException("Azure did not return audio for every accent of the word {$this->word->id}.");
        }
    }
}
