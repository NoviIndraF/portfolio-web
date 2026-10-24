<x-layouts.site title="Page not found">
    <section class="container-site flex min-h-[60vh] flex-col items-start justify-center py-20">
        <p class="eyebrow">404</p>
        <h1 class="mt-2 text-4xl font-extrabold tracking-tight sm:text-5xl">This page doesn't exist</h1>
        <p class="mt-4 max-w-lg text-muted">The link may be old or the project may have been moved.</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('home') }}#projects" class="btn-primary">{{ __('site.cta.view_projects') }}</a>
            <a href="{{ route('home') }}#contact" class="btn-ghost">{{ __('site.cta.contact_me') }}</a>
        </div>
    </section>
</x-layouts.site>
