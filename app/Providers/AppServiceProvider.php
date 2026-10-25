<?php

namespace App\Providers;

use App\Models\PageSection;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ProjectFeature;
use App\Models\ProjectLink;
use App\Models\ProjectTech;
use App\Models\Skill;
use App\Models\SkillGroup;
use App\Services\PortfolioContext;
use App\Support\Site;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(Site::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Settings and profile for the public site layout.
        View::composer('components.layouts.site', function ($view) {
            $site = app(Site::class);
            $view->with(['settings' => $site->settings(), 'profile' => $site->profile(), 'site' => $site]);
        });

        // Chatbot: max messages per visitor per hour.
        RateLimiter::for('chat', fn (Request $request) => Limit::perHour(config('chatbot.per_hour'))->by($request->ip())
            ->response(fn () => response()->json(['reply' => __('site.chat.rate_limited')], 429)));

        // Chatbot: rebuild the portfolio text as soon as content is edited in the admin panel.
        $contentModels = [
            Profile::class, Project::class, ProjectFeature::class, ProjectTech::class, ProjectLink::class,
            SkillGroup::class, Skill::class, PageSection::class,
        ];
        foreach ($contentModels as $model) {
            $model::saved(fn () => PortfolioContext::forget());
            $model::deleted(fn () => PortfolioContext::forget());
        }
    }
}
