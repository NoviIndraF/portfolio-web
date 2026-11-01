<?php

namespace App\Http\Middleware;

use App\Support\Site;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Interface language (lang/{locale}/site.php): the visitor's choice from the ID/EN switch,
 * else their browser language. Only while the switch is on in Admin → Settings.
 */
class SetLocale
{
    public const LOCALES = ['en', 'id'];

    public function handle(Request $request, Closure $next): Response
    {
        if (app(Site::class)->settings()->show_language_switch) {
            $chosen = $request->cookie('locale');
            app()->setLocale(in_array($chosen, self::LOCALES, true) ? $chosen : $request->getPreferredLanguage(self::LOCALES));
        }

        return $next($request);
    }
}
