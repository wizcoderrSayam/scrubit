<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Re-encodes uploaded images to WebP so that no original upload bytes are ever served.
 */
class ImageUploader
{
    public const MAX_DIMENSION = 1600;

    public function store(UploadedFile $file, string $directory = 'services'): string
    {
        return $this->storeFromPath($file->getRealPath(), $directory);
    }

    public function storeFromPath(string $sourcePath, string $directory = 'services'): string
    {
        $contents = file_get_contents($sourcePath);
        $image = $contents === false ? false : @imagecreatefromstring($contents);

        if ($image === false) {
            throw new RuntimeException('The uploaded file is not a readable image.');
        }

        $image = $this->constrain($image);

        ob_start();
        imagewebp($image, null, 82);
        $webp = (string) ob_get_clean();
        imagedestroy($image);

        $path = trim($directory, '/').'/'.Str::random(32).'.webp';
        Storage::disk('public')->put($path, $webp);

        return $path;
    }

    public function delete(?string $path): void
    {
        if ($path !== null && $path !== '') {
            Storage::disk('public')->delete($path);
        }
    }

    private function constrain(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, self::MAX_DIMENSION / max($width, $height));

        if (! imageistruecolor($image)) {
            imagepalettetotruecolor($image);
        }

        if ($scale >= 1) {
            return $image;
        }

        $resized = imagecreatetruecolor((int) round($width * $scale), (int) round($height * $scale));
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, imagesx($resized), imagesy($resized), $width, $height);
        imagedestroy($image);

        return $resized;
    }
}
