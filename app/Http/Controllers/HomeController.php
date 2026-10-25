<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SkillGroup;
use App\Support\Site;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Site $site): View
    {
        $published = Project::published()
            ->with([...Project::publicRelations(), 'webShots' => fn ($q) => $q->visible()])
            ->get();
        // Carousel shows featured projects; if none are marked, show all published ones.
        $featured = $published->where('is_featured', true)->values();
        $skillGroups = SkillGroup::visible()
            ->with(['skills' => fn ($q) => $q->visible()])
            ->orderBy('sort_order')
            ->get()
            ->filter(fn ($g) => $g->skills->isNotEmpty());

        return view('pages.home', [
            'profile' => $site->profile(),
            'sections' => $site->sections(),
            'projects' => $featured->isNotEmpty() ? $featured : $published,
            'publishedCount' => $published->count(),
            'skillGroups' => $skillGroups,
            'skillProjects' => $this->skillProjects($skillGroups->flatMap->skills, $published),
        ]);
    }

    /**
     * Published projects per skill, matched on the projects' technology names.
     *
     * @return array<int, list<array{title: string, href: string, slug: string}>> keyed by skill id
     */
    private function skillProjects(Collection $skills, Collection $projects): array
    {
        $map = [];
        foreach ($skills as $skill) {
            $matches = $projects->filter(fn ($p) => $p->tech->contains(fn ($t) => $this->sameTech($skill->name, $t->name)));
            if ($matches->isNotEmpty()) {
                $map[$skill->id] = $matches->map(fn ($p) => ['title' => $p->title, 'href' => route('projects.show', $p), 'slug' => $p->slug])->values()->all();
            }
        }

        return $map;
    }

    /** "Flutter" matches "Flutter Bloc"; "Bloc" matches "State Management (Bloc, Provider)". */
    private function sameTech(string $skill, string $tech): bool
    {
        $a = Str::lower($skill);
        $b = Str::lower($tech);
        $word = fn (string $needle, string $hay) => (bool) preg_match('/(^|[^a-z0-9])'.preg_quote($needle, '/').'($|[^a-z0-9])/', $hay);

        return $a === $b || $word($a, $b) || $word($b, $a);
    }
}
