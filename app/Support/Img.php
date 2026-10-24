<?php

namespace App\Support;

use App\Services\ImageOptimizer;
use Illuminate\Support\Facades\Storage;

/**
 * Responsive image helpers: srcset with the small "-sm" WebP and the display WebP.
 */
class Img
{
    public static function url(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    /** "small.webp 560w, full.webp 1600w", or null when there is no small variant. */
    public static function srcset(?string $path, ?int $width = null): ?string
    {
        if (! $path) {
            return null;
        }
        $disk = Storage::disk('public');
        $small = ImageOptimizer::smallPath($path);
        if (! $disk->exists($small)) {
            return null;
        }
        $width ??= @getimagesize($disk->path($path))[0] ?: 1600;
        if ($width <= ImageOptimizer::SMALL_WIDTH) {
            return null;
        }

        return $disk->url($small).' '.ImageOptimizer::SMALL_WIDTH.'w, '.$disk->url($path).' '.$width.'w';
    }

    /** Small variant URL if it exists (for tiny images such as avatars). */
    public static function small(?string $path): ?string
    {
        if (! $path) {
            return null;
        }
        $small = ImageOptimizer::smallPath($path);

        return Storage::disk('public')->exists($small) ? Storage::disk('public')->url($small) : self::url($path);
    }
}
