<?php

namespace App\Console\Commands;

use App\Jobs\FetchWordPronunciation;
use App\Models\Word;
use App\Services\OxfordLearnersPronunciation;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

#[Signature('words:fetch-pronunciation {--overwrite : Fetch every word again and replace its IPA and audio} {--delay=2 : Seconds between two queued words, so the dictionary is not flooded}')]
#[Description('Queue the IPA and audio lookup for the words that have none yet')]
class FetchWordPronunciations extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(OxfordLearnersPronunciation $dictionary): int
    {
        $overwrite = (bool) $this->option('overwrite');
        $delay = max(0, (int) $this->option('delay'));
        $disk = Storage::disk('public');
        $queued = 0;

        Word::query()->each(function (Word $word) use ($dictionary, $disk, $overwrite, $delay, &$queued): void {
            if (! $overwrite && ! $this->isMissingPronunciation($word, $dictionary->accents(), $disk)) {
                return;
            }

            FetchWordPronunciation::dispatch($word, $overwrite)->delay(now()->addSeconds($queued * $delay));
            $queued++;
        });

        $this->info("Queued {$queued} word(s).");

        if (! $dictionary->isEnabled()) {
            $this->warn('The dictionary lookup is disabled (OXFORD_LEARNERS_ENABLED), so the queued jobs will not fetch any IPA.');
        }

        return self::SUCCESS;
    }

    /**
     * Whether the word lacks the IPA or the audio file of any accent.
     *
     * @param  list<string>  $accents
     */
    private function isMissingPronunciation(Word $word, array $accents, Filesystem $disk): bool
    {
        foreach ($accents as $accent) {
            if (blank($word->{"ipa_{$accent}"}) || ! $disk->exists("audio/{$accent}/{$word->slug}.mp3")) {
                return true;
            }
        }

        return false;
    }
}
