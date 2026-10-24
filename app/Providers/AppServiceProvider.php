<?php

namespace App\Providers;

use App\Support\Site;
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
    }
}
