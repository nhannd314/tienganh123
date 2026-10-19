<?php

namespace App\Jobs;

use App\Models\Flashcard;
use App\Services\AzureTextToSpeech;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateFlashcardAudio implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 60;

    public function __construct(public Flashcard $flashcard) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Read the card's text aloud with Azure, in a woman's voice or a child's by turns, and store the MP3 on the public disk.
     * Does nothing when the card already has audio, has nothing to read, or Azure is not configured; an answer Azure
     * fails to give is retried.
     */
    public function handle(AzureTextToSpeech $speech): void
    {
        $flashcard = $this->flashcard->fresh();

        if ($flashcard === null || filled($flashcard->audio) || blank($flashcard->display_title) || ! $speech->isConfigured()) {
            return;
        }

        $mp3 = $speech->synthesize($flashcard->display_title, $speech->flashcardVoice($flashcard->id));

        if ($mp3 === null) {
            throw new RuntimeException("Azure did not return audio for flashcard {$flashcard->id}.");
        }

        $path = 'flashcards/audio/'.Str::uuid().'.mp3';

        Storage::disk('public')->put($path, $mp3, 'public');

        $flashcard->update(['audio' => $path]);
    }
}
