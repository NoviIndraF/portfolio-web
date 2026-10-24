<?php

namespace App\Filament\Widgets;

use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectImage;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/** Quick overview of what's live and what's still missing. */
class ContentStatus extends StatsOverviewWidget
{
    protected ?string $heading = 'Content status';

    protected function getStats(): array
    {
        $published = Project::where('is_published', true)->count();
        $drafts = Project::where('is_published', false)->count();
        $hasCv = (bool) Profile::current()?->cvUrl();

        return [
            Stat::make('Published projects', $published)
                ->description($drafts ? "{$drafts} draft(s)" : 'No drafts'),
            Stat::make('Images', ProjectImage::count())
                ->description(ProjectImage::where('kind', 'screenshot')->count().' phone screenshots'),
            Stat::make('CV', $hasCv ? 'Uploaded' : 'Missing')
                ->description($hasCv ? 'Download CV button is visible' : 'Upload it in Profile to show the button')
                ->color($hasCv ? 'success' : 'warning'),
        ];
    }
}
