<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Setting extends Model
{
    protected $fillable = [
        'site_title', 'meta_description', 'primary_color', 'background_color',
        'text_color', 'toggle_3d', 'og_image',
    ];

    protected function casts(): array
    {
        return ['toggle_3d' => 'boolean'];
    }

    /** The single settings row; created with defaults if missing. */
    public static function current(): self
    {
        return static::query()->firstOrCreate([], ['site_title' => config('app.name')]);
    }

    public function ogImageUrl(): ?string
    {
        return $this->og_image ? Storage::disk('public')->url($this->og_image) : null;
    }
}
