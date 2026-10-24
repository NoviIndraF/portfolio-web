<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Support\Site;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /** All published projects, with filter chips by type. */
    public function index(): View
    {
        $projects = Project::published()->with(Project::publicRelations())->get();

        return view('pages.projects', [
            'projects' => $projects,
            'types' => $projects->mapWithKeys(fn ($p) => [$p->type => $p->typeLabel()])->sort(),
        ]);
    }

    public function show(Project $project, Site $site): View
    {
        // Drafts are visible only to a logged-in admin (preview).
        abort_unless($project->is_published || auth()->check(), 404);

        $project->load([
            'links' => fn ($q) => $q->visible(),
            'features' => fn ($q) => $q->visible(),
            'tech' => fn ($q) => $q->visible(),
            'images' => fn ($q) => $q->visible(),
        ]);

        $published = Project::published()->get(['id', 'title', 'slug', 'accent_color']);
        $index = $published->search(fn ($p) => $p->id === $project->id);

        return view('pages.project', [
            'project' => $project,
            'profile' => $site->profile(),
            'prev' => $index === false ? null : $published->get($index - 1),
            'next' => $index === false ? null : $published->get($index + 1),
        ]);
    }
}
