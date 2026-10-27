<?php

namespace App\Console\Commands;

use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillGroup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

/**
 * Exports ALL content (texts, settings, sections, projects, skills) plus the original files
 * (photos, screenshots, CV, 3D models) into one folder. Import it anywhere with `portfolio:import`.
 * Use it for backups and to move from SQLite (laptop) to MySQL (server).
 */
class ExportContent extends Command
{
    protected $signature = 'portfolio:export {path? : Target folder (default: storage/app/exports/<date>)}';

    protected $description = 'Export all portfolio content and files to a folder (backup / move to another database)';

    private string $dir;

    public function handle(): int
    {
        $this->dir = rtrim($this->argument('path') ?? storage_path('app/exports/'.now()->format('Y-m-d_His')), '/');
        File::ensureDirectoryExists($this->dir.'/files');

        $profile = Profile::current();
        $settings = Setting::current();

        $data = [
            'version' => 2,
            'exported_at' => now()->toIso8601String(),
            'profile' => $profile ? [
                'name' => $profile->name,
                'tagline' => $profile->tagline,
                'bio' => $profile->bio,
                'stats' => $profile->stats,
                'email' => $profile->email,
                'github' => $profile->github,
                'linkedin' => $profile->linkedin,
                'phone' => $profile->phone,
                'photo' => $this->copy($profile->photo_original ?: $profile->photo, 'profile'),
                'cv_pdf' => $this->copy($profile->cv_pdf, 'profile'),
                'photos' => $profile->photos->map(fn ($i) => [
                    'file' => $this->copy($i->original_path ?: $i->path, 'profile/gallery'),
                    'caption' => $i->caption,
                    'is_visible' => $i->is_visible,
                ])->filter(fn ($i) => $i['file'])->values()->all(),
            ] : null,
            'settings' => [
                'site_title' => $settings->site_title,
                'meta_description' => $settings->meta_description,
                'primary_color' => $settings->primary_color,
                'background_color' => $settings->background_color,
                'text_color' => $settings->text_color,
                'light_background_color' => $settings->light_background_color,
                'light_text_color' => $settings->light_text_color,
                'default_theme' => $settings->default_theme,
                'toggle_3d' => $settings->toggle_3d,
                'og_image' => $this->copy($settings->og_image, 'settings'),
            ],
            'sections' => PageSection::orderBy('sort_order')->get()->map(fn (PageSection $s) => [
                'type' => $s->type,
                'anchor' => $s->anchor,
                'nav_label' => $s->nav_label,
                'eyebrow' => $s->eyebrow,
                'title' => $s->title,
                'intro' => $s->intro,
                'body' => $s->body,
                'items' => $s->items,
                'is_visible' => $s->is_visible,
                'order' => $s->sort_order,
            ])->all(),
            'projects' => Project::orderBy('sort_order')->with(['links', 'features.image', 'tech', 'images'])->get()->map(fn (Project $p) => [
                'slug' => $p->slug,
                'title' => $p->title,
                'type' => $p->type,
                'year' => $p->year,
                'order' => $p->sort_order,
                'is_published' => $p->is_published,
                'is_featured' => $p->is_featured,
                'scene_type' => $p->scene_type,
                'accent_color' => $p->accent_color,
                'impact_line' => $p->impact_line,
                'short_description' => $p->short_description,
                'problem' => $p->problem,
                'solution' => $p->solution,
                'recognition' => $p->recognition,
                'model_glb' => $this->copy($p->model_glb, $p->slug),
                'features' => $p->features->map(fn ($f) => $f->only(['title', 'description', 'icon', 'is_visible']) + [
                    'screen' => $f->image ? $this->copy($f->image->original_path ?: $f->image->path, $p->slug) : null,
                ])->all(),
                'tech' => $p->tech->map(fn ($t) => $t->only(['name', 'is_visible']))->all(),
                'links' => $p->links->map(fn ($l) => $l->only(['label', 'url', 'is_visible']))->all(),
                'images' => $p->images->map(fn ($i) => [
                    'file' => $this->copy($i->original_path ?: $i->path, $p->slug),
                    'kind' => $i->kind,
                    'caption' => $i->caption,
                    'is_visible' => $i->is_visible,
                ])->filter(fn ($i) => $i['file'])->values()->all(),
            ])->all(),
            'skills' => SkillGroup::orderBy('sort_order')->with('skills')->get()->map(fn ($g) => [
                'group' => $g->name,
                'is_visible' => $g->is_visible,
                'items' => $g->skills->map(fn ($s) => $s->only(['name', 'icon', 'is_visible']))->all(),
            ])->all(),
        ];

        File::put($this->dir.'/content.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

        $this->info('Exported '.count($data['projects']).' projects to:');
        $this->line($this->dir);

        return self::SUCCESS;
    }

    /** Copies a file from the public disk into the export; returns its path relative to the export folder. */
    private function copy(?string $path, string $group): ?string
    {
        $disk = Storage::disk('public');
        if (! $path || ! $disk->exists($path)) {
            return null;
        }
        $relative = 'files/'.$group.'/'.basename($path);
        File::ensureDirectoryExists(dirname($this->dir.'/'.$relative));
        File::copy($disk->path($path), $this->dir.'/'.$relative);

        return $relative;
    }
}
