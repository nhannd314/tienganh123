<?php

namespace App\Jobs;

use App\Models\WordScene;
use App\Services\OpenAiImageGenerator;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GenerateWordSceneImage implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    public function __construct(public WordScene $wordScene) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [30, 120];
    }

    /**
     * Draw all the words of the scene in one cartoon picture with OpenAI and store it on the public disk, replacing (and
     * deleting) the picture the scene has. The rectangles drawn on the old picture no longer fit the new one, so they are cleared.
     * Does nothing when the scene has no word or OpenAI is not configured; an answer OpenAI fails to give is retried.
     */
    public function handle(OpenAiImageGenerator $generator): void
    {
        $scene = $this->wordScene->fresh();

        if ($scene === null || blank(config('services.openai.key'))) {
            return;
        }

        $words = $scene->items()->with('word')->get()->pluck('word.word')->filter()->values()->all();

        if ($words === []) {
            return;
        }

        $png = $generator->generateForWords($words);

        if ($png === null) {
            throw new RuntimeException("OpenAI did not return an image for word scene {$scene->id}.");
        }

        $path = 'word-scenes/'.Str::uuid().'.png';

        Storage::disk('public')->put($path, $png, 'public');

        $previousImage = $scene->image;

        $scene->update(['image' => $path]);
        $scene->items()->update(['x' => null, 'y' => null, 'width' => null, 'height' => null]);

        if (filled($previousImage)) {
            Storage::disk('public')->delete($previousImage);
        }
    }
}
