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
    public function generate(string $word, int $width = 512, int $height = 512): ?string
    {
        $prompt = "A cute cartoon-style illustration representing the word \"{$word}\", ".
            'for a vocabulary flashcard. Centered composition. No borders, no texts';

        $image = $this->request($prompt, $word, ['size' => '1024x1024', 'background' => 'transparent']);

        return $image === null ? null : $this->resize($image, $width, $height);
    }

    /**
     * Generate a cute cartoon-style PNG illustration of the scene of the given sentence, with its background, in landscape,
     * resized to the requested dimensions.
     *
     * Returns the raw PNG file content, or null when the API key is missing or the request fails (the failure is logged).
     */
    public function generateForSentence(string $sentence, int $width = 600, int $height = 400): ?string
    {
        $prompt = "A cute cartoon-style illustration of the scene \"{$sentence}\", ".
            'for a children\'s English lesson. Full scene with a background. No borders, no texts, no letters';

        $image = $this->request($prompt, $sentence, ['size' => '1536x1024']);

        return $image === null ? null : $this->resize($image, $width, $height);
    }

    /**
     * Ask OpenAI for one PNG and return its decoded content, or null when the API key is missing or the request fails.
     *
     * @param  array<string, string>  $options
     */
    private function request(string $prompt, string $subject, array $options): ?string
    {
        $key = config('services.openai.key');

        if (! $key) {
            return null;
        }

        $response = Http::withToken($key)
            ->timeout(90)
            ->post('https://api.openai.com/v1/images/generations', [
                'model' => config('services.openai.image_model'),
                'prompt' => $prompt,
                'output_format' => 'png',
                'n' => 1,
                'quality' => 'medium',
                ...$options,
            ]);

        if ($response->failed()) {
            Log::warning('OpenAI image generation failed.', ['word' => $subject, 'status' => $response->status(), 'error' => data_get($response->json(), 'error.message')]);

            return null;
        }

        $image = base64_decode((string) data_get($response->json(), 'data.0.b64_json'), true);

        if (! $image) {
            Log::warning('OpenAI image generation returned no image.', ['word' => $subject]);

            return null;
        }

        return $image;
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
