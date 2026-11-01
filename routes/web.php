<?php

use App\Http\Controllers\ChatController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\SeoController;
use App\Http\Middleware\SetLocale;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/{project:slug}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/chat', ChatController::class)->middleware('throttle:chat')->name('chat');
Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('sitemap');
// ID / EN switch: remembers the choice for a year and goes back to the same page.
Route::get('/lang/{locale}', fn (string $locale) => back(fallback: route('home'))->withCookie(cookie('locale', $locale, 60 * 24 * 365)))
    ->whereIn('locale', SetLocale::LOCALES)
    ->name('lang');
