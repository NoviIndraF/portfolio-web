<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SkillGroup;
use App\Support\Site;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(Site $site): View
    {
        $published = Project::published()->with(Project::publicRelations())->get();
        // Carousel shows featured projects; if none are marked, show all published ones.
        $featured = $published->where('is_featured', true)->values();

        return view('pages.home', [
            'profile' => $site->profile(),
            'sections' => $site->sections(),
            'projects' => $featured->isNotEmpty() ? $featured : $published,
            'publishedCount' => $published->count(),
            'skillGroups' => SkillGroup::visible()
                ->with(['skills' => fn ($q) => $q->visible()])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn ($g) => $g->skills->isNotEmpty()),
        ]);
    }
}
