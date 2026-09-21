<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CertificadoPdfAssets
{
    private const MAX_BACKGROUND_WIDTH = 1600;

    private const MAX_BACKGROUND_HEIGHT = 1200;

    private const MAX_BACKGROUND_FILESIZE = 4194304;

    public static function resolveBackgroundPath(?string $background): ?string
    {
        if (! is_string($background) || trim($background) === '') {
            return null;
        }

        if (self::isAbsolutePath($background)) {
            return is_readable($background) ? $background : null;
        }

        if (Storage::disk('public')->exists($background)) {
            return Storage::disk('public')->path($background);
        }

        if (Storage::disk('private')->exists($background)) {
            return Storage::disk('private')->path($background);
        }

        if (Storage::exists($background)) {
            return Storage::path($background);
        }

        return null;
    }

    public static function prepareBackgroundForPdf(?string $background): ?string
    {
        $resolvedPath = self::resolveBackgroundPath($background);

        if (! $resolvedPath) {
            return null;
        }

        if (! extension_loaded('gd')) {
            return $resolvedPath;
        }

        $imageInfo = @getimagesize($resolvedPath);
        if (! is_array($imageInfo)) {
            return $resolvedPath;
        }

        [$width, $height, $imageType] = $imageInfo;
        $fileSize = @filesize($resolvedPath) ?: 0;

        $needsOptimization = $imageType === IMAGETYPE_PNG
            || $width > self::MAX_BACKGROUND_WIDTH
            || $height > self::MAX_BACKGROUND_HEIGHT
            || $fileSize > self::MAX_BACKGROUND_FILESIZE;

        if (! $needsOptimization) {
            return $resolvedPath;
        }

        $cacheDirectory = self::backgroundCacheDirectory();
        if (! self::ensureDirectoryIsWritable($cacheDirectory)) {
            return $resolvedPath;
        }

        $cacheKey = sha1($resolvedPath.'|'.(@filemtime($resolvedPath) ?: 0).'|'.$fileSize.'|'.$width.'|'.$height);
        $cachedPath = $cacheDirectory.'/'.$cacheKey.'.jpg';

        if (is_readable($cachedPath)) {
            return $cachedPath;
        }

        $sourceImage = self::createImageResource($resolvedPath, $imageType);
        if (! $sourceImage) {
            return $resolvedPath;
        }

        [$targetWidth, $targetHeight] = self::calculateTargetDimensions($width, $height);

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (! $canvas) {
            imagedestroy($sourceImage);

            return $resolvedPath;
        }

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefill($canvas, 0, 0, $white);

        imagecopyresampled($canvas, $sourceImage, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $saved = imagejpeg($canvas, $cachedPath, 86);

        imagedestroy($canvas);
        imagedestroy($sourceImage);

        return $saved && is_readable($cachedPath) ? $cachedPath : $resolvedPath;
    }

    public static function fontPath(): string
    {
        $storageFont = storage_path('fonts/RobotoCondensed-Bold-700.ttf');

        if (is_readable($storageFont)) {
            return $storageFont;
        }

        return public_path('fonts/RobotoCondensed-Bold-700.ttf');
    }

    public static function fontCacheDirectory(): string
    {
        return (string) config('dompdf.options.font_cache', storage_path('fonts'));
    }

    public static function backgroundCacheDirectory(): string
    {
        return storage_path('app/pdf-backgrounds');
    }

    public static function fontCacheIsWritable(): bool
    {
        $fontCacheDirectory = self::fontCacheDirectory();

        return is_dir($fontCacheDirectory) && is_writable($fontCacheDirectory);
    }

    private static function isAbsolutePath(string $path): bool
    {
        return str_starts_with($path, DIRECTORY_SEPARATOR)
            || preg_match('/^[A-Za-z]:\\\\/', $path) === 1;
    }

    private static function ensureDirectoryIsWritable(string $directory): bool
    {
        if (is_dir($directory)) {
            return is_writable($directory);
        }

        return @mkdir($directory, 0755, true) && is_writable($directory);
    }

    private static function createImageResource(string $path, int $imageType)
    {
        return match ($imageType) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : null,
            default => null,
        };
    }

    private static function calculateTargetDimensions(int $width, int $height): array
    {
        $scale = min(
            1,
            self::MAX_BACKGROUND_WIDTH / max($width, 1),
            self::MAX_BACKGROUND_HEIGHT / max($height, 1)
        );

        return [
            max(1, (int) round($width * $scale)),
            max(1, (int) round($height * $scale)),
        ];
    }
}
