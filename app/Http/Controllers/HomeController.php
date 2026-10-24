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
        $projects = Project::published()
            ->with(['screenshots', 'certificates', 'tech'])
            ->get();

        return view('pages.home', [
            'profile' => $site->profile(),
            'projects' => $projects,
            'skillGroups' => SkillGroup::with('skills')->orderBy('sort_order')->get(),
        ]);
    }
}
