<x-layouts.site :title="__('site.not_found.title')">
    <section class="container-site flex min-h-[70vh] flex-col items-start justify-center py-20" aria-labelledby="not-found-title">
        <p class="eyebrow">&gt; {{ __('site.not_found.eyebrow') }}</p>
        <p class="display mt-4 text-[clamp(7rem,28vw,22rem)] leading-[0.8] text-outline" aria-hidden="true">404</p>
        <h1 id="not-found-title" class="display mt-6 text-[clamp(2.5rem,6vw,5rem)]">{{ __('site.not_found.title') }}</h1>
        <p class="mt-4 max-w-lg text-muted">{{ __('site.not_found.text') }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <a href="{{ route('home') }}#projects" class="btn-primary">{{ __('site.cta.view_projects') }} <x-heroicon-o-arrow-right class="arrow-shift size-4" aria-hidden="true" /></a>
            <a href="{{ route('home') }}#contact" class="btn-ghost">{{ __('site.cta.contact_me') }}</a>
        </div>
    </section>
</x-layouts.site>
