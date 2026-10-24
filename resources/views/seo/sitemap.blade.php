{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <url>
        <loc>{{ route('home') }}</loc>
        <priority>1.0</priority>
    </url>
    <url>
        <loc>{{ route('projects.index') }}</loc>
        <priority>0.9</priority>
    </url>
    @foreach ($projects as $project)
        <url>
            <loc>{{ route('projects.show', $project) }}</loc>
            <lastmod>{{ $project->updated_at?->toAtomString() }}</lastmod>
            <priority>0.8</priority>
        </url>
    @endforeach
</urlset>
