<?php

namespace App\Http\Controllers;

use App\Models\Profile;
use App\Models\Project;
use App\Services\PortfolioContext;
use Illuminate\Http\Response;

/** robots.txt, sitemap.xml and llms.txt, generated so they always use the live domain. */
class SeoController extends Controller
{
    public function robots(): Response
    {
        $body = "User-agent: *\nDisallow: /admin\n\nSitemap: ".route('sitemap')."\n";

        return response($body, 200, ['Content-Type' => 'text/plain']);
    }

    public function sitemap(): Response
    {
        $projects = Project::published()->get(['slug', 'updated_at']);

        return response()->view('seo.sitemap', ['projects' => $projects], 200, ['Content-Type' => 'application/xml']);
    }

    /** llms.txt: the public portfolio content as Markdown for AI tools (the same text the chatbot answers from). */
    public function llms(): Response
    {
        $profile = Profile::current();
        $summary = filled($profile?->tagline) ? "> {$profile->tagline}\n\n" : '';
        // The chatbot text starts its sections at "#"; here they sit one level down, under the name.
        $body = '# '.($profile?->name ?? config('app.name'))."\n\n".$summary.preg_replace('/^(#+) /m', '#$1 ', PortfolioContext::text())."\n";

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
