<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\Sentence;
use App\Services\AzureTextToSpeech;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateSentenceAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public Sentence $sentence) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Read the sentence aloud with Azure, in an American or British woman's, man's or a child's voice by turns, and store the MP3 on the public disk.
     * Does nothing when the sentence already has audio, has nothing to read, or Azure is not configured; an answer Azure
     * fails to give is retried.
     */
    public function handle(AzureTextToSpeech $speech): void
    {
        $sentence = $this->sentence->fresh();

        if ($sentence === null || filled($sentence->audio) || blank(trim(Lesson::spokenSentence($sentence->sentence))) || ! $speech->isConfigured()) {
            return;
        }

        $mp3 = $speech->synthesize(Lesson::spokenSentence($sentence->sentence), $speech->sentenceVoice($sentence->id));

        if ($mp3 === null) {
            throw new RuntimeException("Azure did not return audio for sentence {$sentence->id}.");
        }

        $path = 'lessons/sentence-audio/'.Str::uuid().'.mp3';

        Storage::disk('public')->put($path, $mp3, 'public');

        $sentence->update(['audio' => $path]);
    }
}
