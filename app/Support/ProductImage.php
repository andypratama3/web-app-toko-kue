<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

class ProductImage
{
    public const PLACEHOLDER = 'images/placeholder-product.png';

    public static function exists(?string $path): bool
    {
        $path = trim((string) $path);

        if ($path === '') {
            return false;
        }

        if (self::isExternal($path)) {
            return true;
        }

        if (self::isLegacyPublicPath($path)) {
            return is_file(public_path($path));
        }

        return Storage::disk('public')->exists($path);
    }

    public static function url(?string $path): string
    {
        $path = trim((string) $path);

        if (! self::exists($path)) {
            return self::placeholderUrl();
        }

        if (self::isExternal($path)) {
            return $path;
        }

        if (self::isLegacyPublicPath($path)) {
            return rtrim((string) config('app.url'), '/').'/'.ltrim($path, '/');
        }

        return Storage::disk('public')->url($path);
    }

    public static function placeholderUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.self::PLACEHOLDER;
    }

    private static function isExternal(string $path): bool
    {
        return str_starts_with($path, 'http://') || str_starts_with($path, 'https://');
    }

    private static function isLegacyPublicPath(string $path): bool
    {
        return str_starts_with($path, 'assets/');
    }
}
