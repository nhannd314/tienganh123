<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Image\Image;

class OpenAiImageGenerator
{
    /**
     * Generate a cute cartoon-style PNG illustration for the given word, with
     * its background removed, resized to the requested dimensions.
     *
     * Returns the raw PNG file content, or null when the API key is missing
     * or the request fails (the failure is logged).
     */
    public function generate(string $word, int $width = 200, int $height = 200): ?string
    {
        $key = config('services.openai.key');

        if (! $key) {
            return null;
        }

        $prompt = "A cute, adorable cartoon-style illustration representing the word \"{$word}\", ".
            'for a children\'s vocabulary flashcard. Simple flat design, soft colors, centered composition. No borders, no texts';

        $response = Http::withToken($key)
            ->timeout(90)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => config('services.openai.image_model'),
                'prompt' => $prompt,
                'size' => '1024x1024',
                'quality' => 'low',
                'background' => 'transparent',
                'output_format' => 'png',
                'n' => 1,
            ]);

        if ($response->failed()) {
            Log::warning('OpenAI image generation failed.', ['word' => $word, 'status' => $response->status(), 'error' => data_get($response->json(), 'error.message')]);

            return null;
        }

        $image = base64_decode((string) data_get($response->json(), 'data.0.b64_json'), true);

        if (! $image) {
            Log::warning('OpenAI image generation returned no image.', ['word' => $word]);

            return null;
        }

        return $this->resize($image, $width, $height);
    }

    protected function resize(string $image, int $width, int $height): string
    {
        $basePath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'openai_img_'.Str::uuid();
        $tempPath = $basePath.'.png';
        $outputPath = $basePath.'_resized.png';

        file_put_contents($tempPath, $image);

        try {
            Image::load($tempPath)
                ->resize($width, $height)
                ->format('png')
                ->save($outputPath);

            return file_get_contents($outputPath);
        } finally {
            @unlink($tempPath);
            @unlink($outputPath);
        }
    }
}
