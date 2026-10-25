<?php

namespace App\Providers\Filament;

use App\Filament\Resources\PageSections\PageSectionResource;
use App\Models\PageSection;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationItem;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Icons\Heroicon;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    /**
     * Content menu shortcut to the edit page of one home section, found by its anchor.
     * Hidden when no section has that anchor.
     */
    private static function sectionShortcut(string $label, string $anchor, Heroicon $icon, int $sort): NavigationItem
    {
        $section = fn (): ?PageSection => PageSection::where('anchor', $anchor)->first();

        return NavigationItem::make($label)
            ->url(fn (): string => PageSectionResource::getUrl('edit', ['record' => $section()]))
            ->visible(fn (): bool => $section() !== null)
            ->isActiveWhen(fn (): bool => request()->routeIs(PageSectionResource::getRouteBaseName().'.edit')
                && (string) request()->route('record') === (string) $section()?->getKey())
            ->icon($icon)
            ->group('Content')
            ->sort($sort);
    }

    /** "v1.0.0 · a1b2c3d": release version plus the git commit the code runs on (read from .git, no shell). */
    private static function versionLabel(): string
    {
        $label = 'v'.config('app.version');
        $head = trim((string) @file_get_contents(base_path('.git/HEAD')));
        $hash = str_starts_with($head, 'ref: ')
            ? trim((string) @file_get_contents(base_path('.git/'.substr($head, 5))))
            : $head;

        return preg_match('/^[0-9a-f]{40}$/', $hash) ? $label.' · '.substr($hash, 0, 7) : $label;
    }

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->profile(isSimple: false) // lets the admin change name, email and password
            ->brandName('Portfolio Admin')
            ->colors([
                'primary' => Color::Blue,
            ])
            ->navigationGroups(['Content', 'Site'])
            ->renderHook(PanelsRenderHook::FOOTER, fn (): HtmlString => new HtmlString(
                '<p class="px-6 py-4 text-center text-xs text-gray-500">'.e(self::versionLabel()).'</p>'
            ))
            ->navigationItems([
                self::sectionShortcut('Experience', 'experience', Heroicon::OutlinedBriefcase, 3),
                self::sectionShortcut('Education', 'education', Heroicon::OutlinedAcademicCap, 4),
                NavigationItem::make('View website')
                    ->url(fn () => url('/'), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedGlobeAlt)
                    ->group('Site')
                    ->sort(99),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
