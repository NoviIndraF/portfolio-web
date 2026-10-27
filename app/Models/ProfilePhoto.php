<?php

namespace App\Models;

use App\Models\Concerns\HasVisibility;
use App\Models\Concerns\OptimizesImages;
use App\Support\Img;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * One photo of the personal gallery (About carousel). Uploads become WebP automatically.
 */
class ProfilePhoto extends Model
{
    use HasVisibility;
    use OptimizesImages;

    protected $fillable = [
        'profile_id', 'path', 'original_path', 'caption', 'width', 'height', 'sort_order',
    ];

    protected function optimizedImageColumns(): array
    {
        return ['path' => 'original_path'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(Profile::class);
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /** Responsive srcset (small + display WebP) or null. */
    public function srcset(): ?string
    {
        return Img::srcset($this->path, $this->width);
    }
}
