<?php

namespace App\Models;

use App\Models\Concerns\OptimizesImages;
use App\Models\Concerns\HasVisibility;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectImage extends Model
{
    use HasVisibility;
    use OptimizesImages;

    public const KINDS = [
        'screenshot' => 'Phone screenshot',
        'web' => 'Web',
        'admin' => 'Admin panel',
        'hardware' => 'Hardware',
        'certificate' => 'Certificate',
    ];

    protected $fillable = [
        'project_id', 'path', 'original_path', 'kind', 'caption', 'width', 'height', 'sort_order',
    ];

    protected function optimizedImageColumns(): array
    {
        return ['path' => 'original_path'];
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** Responsive srcset (small + display WebP) or null. */
    public function srcset(): ?string
    {
        return \App\Support\Img::srcset($this->path, $this->width);
    }

    /** Data for the phone component. */
    public function screen(?string $title = null): array
    {
        return [
            'url' => $this->url(),
            'srcset' => $this->srcset(),
            'caption' => trim(($title ? $title.' – ' : '').$this->caption, ' –'),
        ];
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }
}
