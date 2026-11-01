<?php

namespace App\Http\Middleware;

use App\Support\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface language of the public pages (lang/{locale}/site.php): the visitor's choice from the ID / EN switch,
 * else the site default (config app.locale). Not the browser language: texts written in the admin are in one
 * language, so guessing would mix languages on the page. Only while the switch is on in Admin → Settings.
 */
class SetLocale
{
    public const LOCALES = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        if (app(Site::class)->settings()->show_language_switch) {
            $chosen = $request->cookie('locale');
            if (in_array($chosen, self::LOCALES, true)) {
                app()->setLocale($chosen);
            }
        }

        return $next($request);
    }
}
