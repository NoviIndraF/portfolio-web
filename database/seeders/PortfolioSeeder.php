<?php

namespace Database\Seeders;

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
 * Seeds all content from data/content.json and copies images from seed/.
 * Safe to re-run: it replaces existing portfolio content.
 */
class PortfolioSeeder extends Seeder
{
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
        $json = base_path('data/content.json');
        if (! is_file($json)) {
            throw new RuntimeException('Missing data/content.json');
        }
        $data = json_decode(file_get_contents($json), true, flags: JSON_THROW_ON_ERROR);

        $disk = Storage::disk('public');
        $disk->deleteDirectory('profile');
        $disk->deleteDirectory('projects');

        DB::transaction(function () use ($data, $images) {
            $this->seedProfile($data['profile'], $images);
            $this->seedProjects($data['projects'], $images);
            $this->seedSkills($data['skills']);
            $this->seedSettings($data['profile']);
        });
    }

    private function seedProfile(array $p, ImageOptimizer $images): void
    {
        $photo = $this->seedPath($p['photo'] ?? 'seed/profile/photo.jpg');
        $stored = $photo ? $images->store($photo, 'profile') : null;

        // CV is optional: only stored when seed/profile/cv.pdf exists.
        $cv = null;
        if (is_file(base_path('seed/profile/cv.pdf'))) {
            $cv = 'profile/cv.pdf';
            Storage::disk('public')->put($cv, file_get_contents(base_path('seed/profile/cv.pdf')));
        }

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
        Project::query()->delete(); // children cascade

        foreach ($projects as $p) {
            $project = Project::create([
                'title' => $p['title'],
                'slug' => $p['slug'],
                'short_description' => $p['short_description'],
                'impact_line' => $p['impact_line'] ?? null, // draft: owner edits in admin
                'type' => $p['type'],
                'accent_color' => $p['accent_color'],
                'scene_type' => $p['scene_type'],
                'sort_order' => $p['order'],
                'is_published' => true,
                'recognition' => $p['recognition'] ?? null,
            ]);

            // Empty "links" stays empty, so no link buttons are rendered.
            foreach (array_values($p['links'] ?? []) as $i => $link) {
                $project->links()->create($link + ['sort_order' => $i + 1]);
            }
            foreach (array_values($p['features'] ?? []) as $i => $f) {
                $project->features()->create([
                    'title' => $f['title'],
                    'description' => $f['description'] ?? null,
                    'sort_order' => $i + 1,
                ]);
            }
            foreach (array_values($p['tech'] ?? []) as $i => $name) {
                $project->tech()->create(['name' => $name, 'sort_order' => $i + 1]);
            }
            foreach (array_values($p['images'] ?? []) as $i => $img) {
                $file = $this->seedPath($img['file']);
                if (! $file) {
                    $this->command?->warn("Image not found, skipped: {$img['file']}");

                    continue;
                }
                $stored = $images->store($file, "projects/{$p['slug']}");
                $project->images()->create([
                    'path' => $stored['webp'],
                    'original_path' => $stored['original'],
                    'kind' => $img['kind'],
                    'caption' => $img['caption'] ?? null,
                    'width' => $stored['width'],
                    'height' => $stored['height'],
                    'sort_order' => $i + 1,
                ]);
            }

            $this->command?->info("Seeded project: {$p['title']}");
        }
    }

    private function seedSkills(array $groups): void
    {
        SkillGroup::query()->delete();

        foreach (array_values($groups) as $gi => $g) {
            $group = SkillGroup::create(['name' => $g['group'], 'sort_order' => $gi + 1]);
            foreach (array_values($g['items']) as $i => $name) {
                $group->skills()->create([
                    'name' => $name,
                    'icon' => self::SKILL_ICONS[$name] ?? null,
                    'sort_order' => $i + 1,
                ]);
            }
        }
    }

    private function seedSettings(array $profile): void
    {
        Setting::query()->delete();
        Setting::create([
            'site_title' => "{$profile['name']} | Mobile App Developer",
            'meta_description' => $profile['tagline'],
            'primary_color' => '#4C8DFF',
            'background_color' => '#0B1020',
            'text_color' => '#E8ECF6',
            'toggle_3d' => true,
            'og_image' => null, // falls back to profile photo
        ]);
    }

    /** Resolves a "seed/..." path from content.json; null if the file is missing. */
    private function seedPath(string $relative): ?string
    {
        $abs = base_path(trim(explode(' ', $relative)[0]));

        return is_file($abs) ? $abs : null;
    }
}
