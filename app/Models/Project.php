<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Project extends Model
{
    public const SCENE_TYPES = [
        'learning' => 'Learning',
        'donation' => 'Donation',
        'shoe-store' => 'Shoe store',
        'chat' => 'Chat',
        'health-iot' => 'Health IoT',
        'hydroponic' => 'Hydroponic',
        'analytics' => 'Analytics',
        'generic' => 'Generic (floating shapes)',
        'none' => 'None (phone only)',
    ];

    /** Suggestions for the "type" field; any other text is allowed too. */
    public const TYPES = ['mobile', 'mobile+web', 'mobile+iot', 'web', 'iot', 'desktop', 'game', 'library'];

    protected $fillable = [
        'title', 'slug', 'short_description', 'impact_line', 'problem', 'solution',
        'type', 'year', 'accent_color', 'scene_type', 'model_glb', 'sort_order',
        'is_published', 'is_featured', 'recognition',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'recognition' => 'array',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        // DB cascade skips model events, so delete images one by one to clean up files.
        static::deleting(function (Project $project) {
            $project->images()->get()->each->delete();
            if ($project->model_glb) {
                Storage::disk('public')->delete($project->model_glb);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }

    /** Readable type label: translated if known, otherwise the text as entered. */
    public function typeLabel(): string
    {
        $key = 'site.project.types.'.$this->type;

        return __($key) !== $key ? __($key) : str($this->type)->replace(['+', '-', '_'], [' + ', ' ', ' '])->title();
    }

    /** Relations for cards and lists on the public site (hidden items excluded). */
    public static function publicRelations(): array
    {
        return [
            'screenshots' => fn ($q) => $q->visible(),
            'certificates' => fn ($q) => $q->visible(),
            'tech' => fn ($q) => $q->visible(),
        ];
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProjectLink::class)->orderBy('sort_order');
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProjectFeature::class)->orderBy('sort_order');
    }

    public function tech(): HasMany
    {
        return $this->hasMany(ProjectTech::class)->orderBy('sort_order');
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProjectImage::class)->orderBy('sort_order');
    }

    /** Phone screenshots used as PhoneViewer3D textures. Empty = show placeholder screen. */
    public function screenshots(): HasMany
    {
        return $this->images()->where('kind', 'screenshot');
    }

    public function certificates(): HasMany
    {
        return $this->images()->where('kind', 'certificate');
    }

    public function modelUrl(): ?string
    {
        return $this->model_glb ? Storage::disk('public')->url($this->model_glb) : null;
    }
}
