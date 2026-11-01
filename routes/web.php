<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\SetLocale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public pages follow the visitor's ID / EN choice; the admin (Filament + Livewire) keeps its own language.
Route::middleware(SetLocale::class)->group(function () {
    Route::get('/', HomeController::class)->name('home');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/chat', ChatController::class)->middleware('throttle:chat')->name('chat');
});
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
// ID / EN switch: remembers the choice for a year and goes back to the same page.
// Only back to this site: the Referer header comes from the visitor (no open redirect).
Route::get('/lang/{locale}', function (Request $request, string $locale) {
    $back = url()->previous();
    $target = parse_url($back, PHP_URL_HOST) === $request->getHost() ? $back : route('home');

    return redirect($target)->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->whereIn('locale', SetLocale::LOCALES)->name('lang');
