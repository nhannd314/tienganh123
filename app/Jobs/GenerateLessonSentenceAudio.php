<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Services\AzureTextToSpeech;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateLessonSentenceAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Lesson $lesson) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Read aloud with Azure, in a random adult US voice like the words, every example sentence of the lesson that has no audio, and store the MP3s on the public disk.
     * The audio is written back to the sentences as they are once Azure has answered, so an edit made meanwhile is kept.
     * Does nothing when Azure is not configured; a sentence Azure fails to read is retried.
     */
    public function handle(AzureTextToSpeech $speech): void
    {
        $lesson = $this->lesson->fresh();

        if ($lesson === null || ! $speech->isConfigured()) {
            return;
        }

        $audioBySentence = [];
        $failed = false;

        foreach (Lesson::sentencesWithoutAudio($lesson->example_sentences) as $sentence) {
            $mp3 = $speech->synthesize(Lesson::spokenSentence($sentence), $speech->wordVoice('us'));

            if ($mp3 === null) {
                $failed = true;

                continue;
            }

            $path = 'lessons/sentence-audio/'.Str::uuid().'.mp3';
            Storage::disk('public')->put($path, $mp3, 'public');
            $audioBySentence[$sentence] = $path;
        }

        if ($audioBySentence !== [] && ($latest = $lesson->fresh()) !== null) {
            $latest->update([
                'example_sentences' => collect($latest->example_sentences ?? [])
                    ->map(function (mixed $example) use ($audioBySentence): mixed {
                        if (is_array($example) && blank($example['audio'] ?? null) && isset($audioBySentence[$example['sentence'] ?? ''])) {
                            $example['audio'] = $audioBySentence[$example['sentence']];
                        }

                        return $example;
                    })
                    ->all(),
            ]);
        }

        if ($failed) {
            throw new RuntimeException("Azure did not return audio for every example sentence of lesson {$lesson->id}.");
        }
    }
}
