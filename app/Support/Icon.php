<?php

namespace App\Support;

use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

/**
 * Inline brand icons from simple-icons (copied to public/icons/simple at build time).
 */
class Icon
{
    /** Tech names whose icon slug can't be derived from the name. */
    private const ALIASES = [
        'firebase core' => 'firebase',
        'cloud firestore' => 'firebase',
        'laravel echo' => 'laravel',
        'pusher client' => 'pusher',
        'google fonts' => 'googlefonts',
        'lottie' => 'lottiefiles',
        'material design' => 'materialdesign',
    ];

    /** @var array<string, string|null> */
    private static array $cache = [];

    /** First slug that has an icon file: explicit slug, alias, then the slugified name. */
    public static function slugFor(string $name, ?string $explicit = null): ?string
    {
        $lower = Str::lower(trim($name));
        $candidates = array_filter([
            $explicit,
            self::ALIASES[$lower] ?? null,
            self::slugify($lower),
        ]);

        foreach ($candidates as $slug) {
            if (is_file(self::path($slug))) {
                return $slug;
            }
        }

        return null;
    }

    public static function svg(?string $slug, string $class = 'size-4'): ?HtmlString
    {
        if (! $slug) {
            return null;
        }

        $raw = self::$cache[$slug] ??= (is_file(self::path($slug)) ? file_get_contents(self::path($slug)) : null);
        if (! $raw) {
            return null;
        }

        $svg = preg_replace(['/<title>.*?<\/title>/', '/ role="img"/'], '', $raw);
        $svg = str_replace('<svg ', '<svg class="'.e($class).'" fill="currentColor" aria-hidden="true" focusable="false" ', $svg);

        return new HtmlString($svg);
    }

    /** simple-icons slug rules (simplified). */
    private static function slugify(string $name): string
    {
        $name = str_replace(['+', '.', '&'], ['plus', 'dot', 'and'], $name);

        return preg_replace('/[^a-z0-9]/', '', Str::ascii($name));
    }

    private static function path(string $slug): string
    {
        return public_path('icons/simple/'.basename($slug).'.svg');
    }
}
