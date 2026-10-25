<?php

namespace App\Services;

use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use Illuminate\Support\Facades\Cache;

/**
 * The portfolio as plain text for the chatbot. Only content that is visible on the website is included,
 * so anything hidden in the admin panel is also unknown to the AI.
 */
class PortfolioContext
{
    private const CACHE_KEY = 'chatbot.portfolio_context';

    public static function text(): string
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(30), fn () => static::build());
    }

    /** Call after content changes so the chatbot sees it right away. */
    public static function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public static function build(): string
    {
        return implode("\n", array_merge(
            static::profile(),
            static::projects(),
            static::skills(),
            static::timelines(),
        ));
    }

    private static function profile(): array
    {
        $profile = Profile::current();

        if (! $profile) {
            return [];
        }

        $out = ['# PROFILE', "Name: {$profile->name}", "Tagline: {$profile->tagline}"];

        if (filled($profile->bio)) {
            $out[] = 'Bio: '.static::clean($profile->bio);
        }
        foreach ($profile->stats ?? [] as $stat) {
            if (filled($stat['label'] ?? null)) {
                $out[] = "- {$stat['label']}: ".($stat['value'] ?? '');
            }
        }

        $out[] = "Email: {$profile->email}";
        foreach (['linkedin' => 'LinkedIn', 'github' => 'GitHub'] as $field => $label) {
            if (filled($profile->{$field})) {
                $out[] = "{$label}: {$profile->{$field}}";
            }
        }
        if ($profile->cvUrl()) {
            $out[] = 'CV (PDF): '.url($profile->cvUrl());
        }

        return $out;
    }

    private static function projects(): array
    {
        $projects = Project::published()->with([
            'tech' => fn ($q) => $q->visible(),
            'features' => fn ($q) => $q->visible(),
            'links' => fn ($q) => $q->visible(),
        ])->get();

        if ($projects->isEmpty()) {
            return [];
        }

        $out = ['', '# PROJECTS'];

        foreach ($projects as $p) {
            $out[] = '';
            $out[] = "## {$p->title}";
            $out[] = 'Type: '.$p->typeLabel().(filled($p->year) ? " | Year: {$p->year}" : '');
            $out[] = 'Page: '.route('projects.show', $p);
            $out[] = 'Summary: '.static::clean($p->short_description);

            foreach (['impact_line' => 'Impact', 'problem' => 'Problem', 'solution' => 'Solution'] as $field => $label) {
                if (filled($p->{$field})) {
                    $out[] = "{$label}: ".static::clean($p->{$field});
                }
            }
            if ($p->tech->isNotEmpty()) {
                $out[] = 'Tech: '.$p->tech->pluck('name')->implode(', ');
            }
            foreach ($p->features as $feature) {
                $out[] = "- Feature: {$feature->title}".(filled($feature->description) ? ' — '.static::clean($feature->description) : '');
            }
            foreach ($p->links as $link) {
                $out[] = "- Link ({$link->label}): {$link->url}";
            }
        }

        return $out;
    }

    private static function skills(): array
    {
        $groups = SkillGroup::visible()->orderBy('sort_order')
            ->with(['skills' => fn ($q) => $q->visible()])
            ->get()
            ->filter(fn ($g) => $g->skills->isNotEmpty());

        if ($groups->isEmpty()) {
            return [];
        }

        return ['', '# SKILLS', ...$groups->map(fn ($g) => "{$g->name}: ".$g->skills->pluck('name')->implode(', '))];
    }

    /** Custom timeline sections (experience, education, certificates…). */
    private static function timelines(): array
    {
        $out = [];

        foreach (PageSection::visible()->where('type', 'timeline')->orderBy('sort_order')->get() as $section) {
            $items = collect($section->items ?? [])
                ->filter(fn ($i) => ($i['is_visible'] ?? true) && filled($i['title'] ?? null));

            if ($items->isEmpty()) {
                continue;
            }

            $out[] = '';
            $out[] = '# '.strtoupper($section->title ?: 'Timeline');
            foreach ($items as $item) {
                $out[] = '- '.collect([$item['period'] ?? null, $item['title'], $item['subtitle'] ?? null, $item['description'] ?? null])
                    ->filter(fn ($v) => filled($v))
                    ->map(fn ($v) => static::clean((string) $v))
                    ->implode(' | ');
            }
        }

        return $out;
    }

    private static function clean(?string $text): string
    {
        return str(strip_tags((string) $text))->squish()->toString();
    }
}
