<?php

namespace App\Services;

use GdImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Turns an uploaded photo into the avatar stored for a learner: upright, cropped to the centre square and scaled down.
 */
class AvatarImage
{
    public const SIZE = 512;

    private const DIRECTORY = 'avatars';

    /**
     * Process the upload and store it on the public disk; returns the stored path.
     *
     * @throws RuntimeException when the file cannot be read as an image.
     */
    public function store(UploadedFile $file): string
    {
        $source = $this->open($file);
        $width = imagesx($source);
        $height = imagesy($source);
        $side = min($width, $height);

        $avatar = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($avatar, 0, 0, imagecolorallocate($avatar, 255, 255, 255));
        imagecopyresampled($avatar, $source, 0, 0, intdiv($width - $side, 2), intdiv($height - $side, 2), self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($avatar, null, 85);
        $contents = ob_get_clean();

        $path = self::DIRECTORY.'/'.Str::uuid().'.jpg';
        Storage::disk('public')->put($path, $contents);

        return $path;
    }

    /**
     * The upload as a GD image, turned upright by its EXIF orientation (phones store photos sideways).
     */
    private function open(UploadedFile $file): GdImage
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            throw new RuntimeException('The file is not an image.');
        }

        $orientation = $file->getMimeType() === 'image/jpeg' && function_exists('exif_read_data')
            ? (@exif_read_data($file->getRealPath())['Orientation'] ?? 1)
            : 1;

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };

        return $rotated ?: $image;
    }
}
