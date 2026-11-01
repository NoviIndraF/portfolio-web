<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = [
        'site_title', 'meta_description', 'primary_color', 'background_color',
        'text_color', 'light_background_color', 'light_text_color', 'default_theme', 'toggle_3d', 'og_image',
        'show_language_switch', 'analytics_script_url', 'analytics_site_id',
    ];

    public const THEMES = ['system' => 'Follow the visitor\'s device', 'light' => 'Light', 'dark' => 'Dark'];

    protected function casts(): array
    {
        return ['toggle_3d' => 'boolean', 'show_language_switch' => 'boolean'];
    }

    /** The single settings row; created with defaults if missing. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['site_title' => config('app.name')]);
    }

    /** Near-black or white, whichever reads better on the primary color (WCAG contrast). */
    public function onPrimaryColor(): string
    {
        $hex = str_pad(substr(ltrim((string) $this->primary_color, '#'), 0, 6), 6, '0');
        $linear = array_map(function (string $pair): float {
            $c = hexdec($pair) / 255;

            return $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }, str_split($hex, 2));
        $luminance = 0.2126 * $linear[0] + 0.7152 * $linear[1] + 0.0722 * $linear[2];

        // Contrast with #0E0E0E (luminance ≈ 0.0044) versus with white.
        return ($luminance + 0.05) / 0.0544 >= 1.05 / ($luminance + 0.05) ? '#0E0E0E' : '#FFFFFF';
    }

    public function ogImageUrl(): ?string
    {
        return $this->og_image ? Storage::disk('public')->url($this->og_image) : null;
    }
}
