<?php

namespace App\Jobs;

use App\Models\Flashcard;
use App\Services\OpenAiImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateFlashcardImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public Flashcard $flashcard) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Generate the cartoon illustration of the card with OpenAI and store it on the public disk.
     * Does nothing when the card already has an image, has nothing to draw, or OpenAI is not configured; an answer
     * OpenAI fails to give is retried.
     */
    public function handle(OpenAiImageGenerator $generator): void
    {
        $flashcard = $this->flashcard->fresh();

        if ($flashcard === null || filled($flashcard->image) || blank($flashcard->display_title) || blank(config('services.openai.key'))) {
            return;
        }

        $png = $generator->generate($flashcard->display_title);

        if ($png === null) {
            throw new RuntimeException("OpenAI did not return an image for flashcard {$flashcard->id}.");
        }

        $path = 'flashcards/images/'.Str::uuid().'.png';

        Storage::disk('public')->put($path, $png, 'public');

        $flashcard->update(['image' => $path]);
    }
}
