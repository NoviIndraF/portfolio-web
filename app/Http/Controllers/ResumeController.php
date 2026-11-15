<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\SkillGroup;
use App\Support\Site;
use Illuminate\View\View;

/**
 * GET /resume — a one-page résumé for printing or "Save as PDF".
 * Built from the same admin content as the home page, so it never goes out of date.
 */
class ResumeController extends Controller
{
    public function __invoke(Site $site): View
    {
        $published = Project::published()->get();
        $featured = $published->where('is_featured', true)->values();

        return view('pages.resume', [
            'profile' => $site->profile(),
            'settings' => $site->settings(),
            // Same order and visibility as the home page (Admin → Home sections).
            'sections' => $site->sections(),
            // Four fit the page; the rest are one click away on the site.
            'projects' => ($featured->isNotEmpty() ? $featured : $published)->take(4),
            'skillGroups' => SkillGroup::visible()
                ->with(['skills' => fn ($q) => $q->visible()])
                ->orderBy('sort_order')
                ->get()
                ->filter(fn ($g) => $g->skills->isNotEmpty()),
        ]);
    }
}
