<?php

use App\Services\OpenAiImageGenerator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * A transparent PNG of the given size, as OpenAI returns it (base64 encoded).
 */
function openAiPngResponse(int $size = 64): array
{
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, false);
    imagesavealpha($image, true);
    imagefill($image, 0, 0, imagecolorallocatealpha($image, 0, 0, 0, 127));

    ob_start();
    imagepng($image);

    return ['data' => [['b64_json' => base64_encode((string) ob_get_clean())]]];
}

beforeEach(function () {
    config(['services.openai.key' => 'openai-key', 'services.openai.image_model' => 'gpt-image-1.5']);
});

it('asks OpenAI for a transparent cartoon PNG of the word and returns it resized, still transparent', function () {
    Http::fake(['api.openai.com/*' => Http::response(openAiPngResponse(64))]);

    $png = app(OpenAiImageGenerator::class)->generate('apple', 40, 30);
    $image = imagecreatefromstring($png);

    expect(imagesx($image))->toBe(40)
        ->and(imagesy($image))->toBe(30)
        ->and((imagecolorat($image, 1, 1) >> 24) & 127)->toBe(127);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.openai.com/v1/images/generations'
        && $request->hasHeader('Authorization', 'Bearer openai-key')
        && $request['model'] === 'gpt-image-1.5'
        && $request['background'] === 'transparent'
        && $request['output_format'] === 'png'
        && $request['quality'] === 'medium'
        && str_contains($request['prompt'], '"apple"'));
});

it('leaves no temporary file behind', function () {
    Http::fake(['api.openai.com/*' => Http::response(openAiPngResponse())]);
    $before = glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'openai_img_*');

    app(OpenAiImageGenerator::class)->generate('apple');

    expect(glob(sys_get_temp_dir().DIRECTORY_SEPARATOR.'openai_img_*'))->toBe($before);
});

it('returns null without calling OpenAI when there is no key', function () {
    Http::fake();
    config(['services.openai.key' => null]);

    expect(app(OpenAiImageGenerator::class)->generate('apple'))->toBeNull();

    Http::assertNothingSent();
});

it('returns null and logs the reason when OpenAI answers with an error or no image', function () {
    Log::spy();
    Http::fakeSequence('api.openai.com/*')
        ->push(['error' => ['message' => 'Transparent background is not supported for this model.']], 400)
        ->push(['data' => []]);

    expect(app(OpenAiImageGenerator::class)->generate('apple'))->toBeNull()
        ->and(app(OpenAiImageGenerator::class)->generate('apple'))->toBeNull();

    Log::shouldHaveReceived('warning')->twice();
});

it('asks OpenAI for a landscape scene of the sentence with its background and returns it resized', function () {
    Http::fake(['api.openai.com/*' => Http::response(openAiPngResponse(64))]);

    $image = imagecreatefromstring(app(OpenAiImageGenerator::class)->generateForSentence('I eat an apple.', 60, 40));

    expect(imagesx($image))->toBe(60)->and(imagesy($image))->toBe(40);

    Http::assertSent(fn ($request) => $request['size'] === '1536x1024'
        && $request['quality'] === 'medium'
        && ! isset($request['background'])
        && str_contains($request['prompt'], '"I eat an apple."'));
});
