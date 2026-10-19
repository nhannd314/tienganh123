<?php

namespace App\Jobs;

use App\Models\Lesson;
use App\Models\Sentence;
use App\Services\OpenAiImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateSentenceImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Sentence $sentence, public bool $replace = false) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Generate the cartoon illustration of the sentence with OpenAI and store it on the public disk.
     * Does nothing when the sentence already has an image (unless it is to be replaced, which deletes the old file), has nothing to
     * draw, or OpenAI is not configured; an answer OpenAI fails to give is retried.
     */
    public function handle(OpenAiImageGenerator $generator): void
    {
        $sentence = $this->sentence->fresh();

        if ($sentence === null || (filled($sentence->image) && ! $this->replace) || blank(trim(Lesson::spokenSentence($sentence->sentence))) || blank(config('services.openai.key'))) {
            return;
        }

        $png = $generator->generateForSentence(Lesson::spokenSentence($sentence->sentence));

        if ($png === null) {
            throw new RuntimeException("OpenAI did not return an image for sentence {$sentence->id}.");
        }

        $path = 'lessons/sentence-images/'.Str::uuid().'.png';

        Storage::disk('public')->put($path, $png, 'public');

        $previousImage = $sentence->image;

        $sentence->update(['image' => $path]);

        if (filled($previousImage)) {
            Storage::disk('public')->delete($previousImage);
        }
    }
}
