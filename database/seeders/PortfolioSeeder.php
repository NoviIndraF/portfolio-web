<?php

namespace Database\Seeders;

use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Setting;
use App\Models\SkillGroup;
use App\Services\ImageOptimizer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Imports all content from a content.json file and the files next to it, REPLACING existing content.
 *
 * Reads data/content.json + seed/ by default, or an export folder made by `php artisan portfolio:export`
 * (see `php artisan portfolio:import`). File paths in the JSON are relative to that folder.
 * Every field beyond the original format is optional, so the first content.json keeps working.
 */
class PortfolioSeeder extends Seeder
{
    /** Folder that contains content.json (or data/content.json) and the referenced files. */
    public ?string $root = null;

    /** Map of skill names to simple-icons slugs (only where a brand icon exists). */
    private const SKILL_ICONS = [
        'Flutter' => 'flutter',
        'Android' => 'android',
        'Laravel' => 'laravel',
        'Firebase' => 'firebase',
        'Cloud Firestore' => 'firebase',
        'Arduino' => 'arduino',
        'Material Design' => 'materialdesign',
    ];

    public function run(ImageOptimizer $images): void
    {
        $root = rtrim($this->root ?? base_path(), '/');
        $json = collect(["{$root}/content.json", "{$root}/data/content.json"])->first(fn ($f) => is_file($f));
        if (! $json) {
            throw new RuntimeException("No content.json found in {$root}");
        }
        $this->root = $root;
        $data = json_decode(file_get_contents($json), true, flags: JSON_THROW_ON_ERROR);

        $disk = Storage::disk('public');
        foreach (['profile', 'projects', 'models', 'og'] as $dir) {
            $disk->deleteDirectory($dir);
        }

        DB::transaction(function () use ($data, $images) {
            $this->seedProfile($data['profile'], $images);
            $this->seedProjects($data['projects'] ?? [], $images);
            $this->seedSkills($data['skills'] ?? []);
            $this->seedSettings($data['settings'] ?? [], $data['profile']);
            if (isset($data['sections'])) {
                $this->seedSections($data['sections']);
            }
        });
    }

    private function seedProfile(array $p, ImageOptimizer $images): void
    {
        $photo = $this->file($p['photo'] ?? 'seed/profile/photo.jpg');
        $stored = $photo ? $images->store($photo, 'profile') : null;

        // CV is optional: hidden on the site when missing.
        $cv = $this->copyFile($p['cv_pdf'] ?? 'seed/profile/cv.pdf', 'profile/cv.pdf');

        Profile::query()->delete();
        Profile::create([
            'name' => $p['name'],
            'tagline' => $p['tagline'],
            'bio' => $p['bio'] ?? null,
            'stats' => $p['stats'] ?? [],
            'photo' => $stored['webp'] ?? null,
            'photo_original' => $stored['original'] ?? null,
            'cv_pdf' => $cv,
            'email' => $p['email'],
            'github' => $p['github'] ?? null,
            'linkedin' => $p['linkedin'] ?? null,
            'phone' => $p['phone'] ?? null,
        ]);
    }

    private function seedProjects(array $projects, ImageOptimizer $images): void
    {
        Project::query()->get()->each->delete(); // model events remove old files

        foreach (array_values($projects) as $n => $p) {
            $project = Project::create([
                'title' => $p['title'],
                'slug' => $p['slug'],
                'short_description' => $p['short_description'],
                'impact_line' => $p['impact_line'] ?? null,
                'problem' => $p['problem'] ?? null,
                'solution' => $p['solution'] ?? null,
                'type' => $p['type'] ?? 'mobile',
                'year' => $p['year'] ?? null,
                'accent_color' => $p['accent_color'] ?? '#4C8DFF',
                'scene_type' => $p['scene_type'] ?? 'generic',
                'model_glb' => $this->copyFile($p['model_glb'] ?? null, "models/{$p['slug']}.glb"),
                'sort_order' => $p['order'] ?? $n + 1,
                'is_published' => $p['is_published'] ?? true,
                'is_featured' => $p['is_featured'] ?? true,
                'recognition' => $p['recognition'] ?? null,
            ]);

            // Empty "links" stays empty, so no link buttons are rendered.
            foreach (array_values($p['links'] ?? []) as $i => $link) {
                $project->links()->create([
                    'label' => $link['label'],
                    'url' => $link['url'],
                    'is_visible' => $link['is_visible'] ?? true,
                    'sort_order' => $i + 1,
                ]);
            }
            foreach (array_values($p['features'] ?? []) as $i => $f) {
                $project->features()->create([
                    'title' => $f['title'],
                    'description' => $f['description'] ?? null,
                    'icon' => $f['icon'] ?? null,
                    'is_visible' => $f['is_visible'] ?? true,
                    'sort_order' => $i + 1,
                ]);
            }
            foreach (array_values($p['tech'] ?? []) as $i => $t) {
                $t = is_string($t) ? ['name' => $t] : $t;
                $project->tech()->create(['name' => $t['name'], 'is_visible' => $t['is_visible'] ?? true, 'sort_order' => $i + 1]);
            }
            foreach (array_values($p['images'] ?? []) as $i => $img) {
                $file = $this->file($img['file']);
                if (! $file) {
                    $this->command?->warn("Image not found, skipped: {$img['file']}");

                    continue;
                }
                $stored = $images->store($file, "projects/{$p['slug']}");
                $project->images()->create([
                    'path' => $stored['webp'],
                    'original_path' => $stored['original'],
                    'kind' => $img['kind'] ?? 'screenshot',
                    'caption' => $img['caption'] ?? null,
                    'is_visible' => $img['is_visible'] ?? true,
                    'width' => $stored['width'],
                    'height' => $stored['height'],
                    'sort_order' => $i + 1,
                ]);
            }

            $this->command?->info("Imported project: {$p['title']}");
        }
    }

    private function seedSkills(array $groups): void
    {
        SkillGroup::query()->delete();

        foreach (array_values($groups) as $gi => $g) {
            $group = SkillGroup::create([
                'name' => $g['group'],
                'is_visible' => $g['is_visible'] ?? true,
                'sort_order' => $gi + 1,
            ]);
            foreach (array_values($g['items']) as $i => $item) {
                $item = is_string($item) ? ['name' => $item] : $item;
                $group->skills()->create([
                    'name' => $item['name'],
                    'icon' => $item['icon'] ?? self::SKILL_ICONS[$item['name']] ?? null,
                    'is_visible' => $item['is_visible'] ?? true,
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }

    private function seedSettings(array $s, array $profile): void
    {
        Setting::query()->delete();
        Setting::create([
            'site_title' => $s['site_title'] ?? "{$profile['name']} | Mobile App Developer",
            'meta_description' => $s['meta_description'] ?? $profile['tagline'],
            'primary_color' => $s['primary_color'] ?? '#4C8DFF',
            'background_color' => $s['background_color'] ?? '#0B1020',
            'text_color' => $s['text_color'] ?? '#E8ECF6',
            'toggle_3d' => $s['toggle_3d'] ?? true,
            'og_image' => $this->copyFile($s['og_image'] ?? null, 'og/'.basename((string) ($s['og_image'] ?? 'og.png'))),
        ]);
    }

    private function seedSections(array $sections): void
    {
        PageSection::query()->get()->each(fn ($s) => $s->deleteQuietly());
        foreach (array_values($sections) as $i => $s) {
            $section = new PageSection([
                'type' => $s['type'],
                'anchor' => $s['anchor'],
                'nav_label' => $s['nav_label'] ?? null,
                'eyebrow' => $s['eyebrow'] ?? null,
                'title' => $s['title'] ?? null,
                'intro' => $s['intro'] ?? null,
                'body' => $s['body'] ?? null,
                'items' => $s['items'] ?? null,
                'sort_order' => $s['order'] ?? $i + 1,
            ]);
            $section->is_visible = $s['is_visible'] ?? true;
            $section->save();
        }
    }

    /** Absolute path of a file referenced in the JSON, or null if missing. */
    private function file(?string $relative): ?string
    {
        if (! $relative) {
            return null;
        }
        $abs = $this->root.'/'.ltrim(trim(explode(' ', $relative)[0]), '/');

        return is_file($abs) ? $abs : null;
    }

    /** Copies a referenced file to the public disk; returns the stored path or null. */
    private function copyFile(?string $relative, string $target): ?string
    {
        $file = $this->file($relative);
        if (! $file) {
            return null;
        }
        Storage::disk('public')->put($target, file_get_contents($file));

        return $target;
    }
}
