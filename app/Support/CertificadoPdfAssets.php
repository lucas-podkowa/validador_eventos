<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class CertificadoPdfAssets
{
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
}