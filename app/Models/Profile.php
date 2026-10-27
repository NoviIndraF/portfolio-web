<?php

namespace App\Models;

use App\Models\Concerns\OptimizesImages;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Profile extends Model
{
    use OptimizesImages;

    protected $fillable = [
        'name', 'tagline', 'bio', 'stats', 'photo', 'photo_original',
        'cv_pdf', 'email', 'github', 'linkedin', 'phone',
    ];

    protected function casts(): array
    {
        return ['stats' => 'array'];
    }

    protected function optimizedImageColumns(): array
    {
        return ['photo' => 'photo_original'];
    }

    /** The single profile row (site owner). */
    public static function current(): ?self
    {
        return static::query()->first();
    }

    /** Personal gallery (About carousel), in admin order. */
    public function photos(): HasMany
    {
        return $this->hasMany(ProfilePhoto::class)->orderBy('sort_order');
    }

    public function photoUrl(): ?string
    {
        return $this->photo ? Storage::disk('public')->url($this->photo) : null;
    }

    /** Null when no CV is uploaded, so the "Download CV" button can hide itself. */
    public function cvUrl(): ?string
    {
        return $this->cv_pdf && Storage::disk('public')->exists($this->cv_pdf)
            ? Storage::disk('public')->url($this->cv_pdf)
            : null;
    }
}
