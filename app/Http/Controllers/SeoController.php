<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Response;

/** robots.txt and sitemap.xml, generated so they always use the live domain. */
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
}
