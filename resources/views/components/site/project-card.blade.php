@props(['project', 'featured' => false, 'expandable' => false])
@php
    $shot = $project->screenshots->first();
    $cert = $project->certificates->first();
    // Hover preview: cycle a few small screenshots (resources/js/ui/interactions.js).
    $previews = $project->screenshots->take(3)->map(fn ($s) => \App\Support\Img::small($s->path))->values();
    // Home rail: the hovered card widens and shows a second phone (resources/js/ui/carousel3d.js); the featured one starts open.
    $second = $expandable ? $project->screenshots->get(1) : null;
@endphp
<article {{ $attributes->class([
             'group relative w-[82vw] max-w-[22rem] shrink-0 snap-start sm:w-[22rem]',
             'transition-[width,max-width] duration-500 ease-out motion-reduce:transition-none data-[expanded]:max-w-[34rem] sm:data-[expanded]:w-[34rem]' => $expandable,
         ]) }}
         style="--accent: {{ $project->accent_color }}" data-tilt data-project-card data-slug="{{ $project->slug }}"
         @if ($expandable) data-expandable @endif @if ($expandable && $featured) data-expanded @endif
         @if ($previews->count() > 1) data-previews="{{ $previews->toJson() }}" @endif>
    <div class="card relative flex h-full flex-col overflow-hidden transition duration-300 group-hover:border-accent/60">
        {{-- Accent glow --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-[radial-gradient(60%_80%_at_50%_0%,color-mix(in_oklab,var(--accent,var(--c-primary))_40%,transparent),transparent)]" aria-hidden="true"></div>

        <div class="relative flex h-64 items-end justify-center gap-6 overflow-hidden pt-6">
            @if ($featured)
                <span class="absolute top-4 left-4 z-10 chip border-accent/40 bg-bg/70 backdrop-blur">
                    <x-heroicon-s-star class="size-4 text-accent-fg" aria-hidden="true" /> {{ __('site.projects.featured') }}
                </span>
            @endif
            <x-site.phone :project="$project" :interactive="false" width="9.5rem"
                          :screens="$shot ? [$shot->screen($project->title)] : []"
                          class="translate-y-16 transition duration-500 group-hover:translate-y-12" />
            @if ($second)
                <x-site.phone :project="$project" :interactive="false" width="9.5rem"
                              :screens="[$second->screen($project->title)]"
                              class="hidden translate-y-24 transition duration-500 group-hover:translate-y-16 sm:group-data-[expanded]:flex" />
            @endif
            @if (! $shot && $cert)
                <span class="absolute top-4 right-4 chip border-accent/40 bg-bg/70 backdrop-blur">
                    <x-heroicon-s-check-badge class="size-4 text-accent-fg" aria-hidden="true" /> HKI
                </span>
            @endif
        </div>

        <div class="relative flex flex-1 flex-col border-t border-line bg-surface p-5">
            <p class="eyebrow">{{ $project->typeLabel() }}@if ($project->year) · {{ $project->year }}@endif</p>
            <h3 class="item-title mt-2 transition-colors group-hover:text-accent-fg">
                <a href="{{ route('projects.show', $project) }}" class="after:absolute after:inset-0" data-project-link>
                    {{ $project->title }}
                </a>
            </h3>
            <p class="mt-2 text-sm text-muted text-pretty">{{ $project->impact_line ?: $project->short_description }}</p>
            <div class="mt-4 flex flex-wrap gap-1.5">
                @foreach ($project->tech->take(3) as $t)
                    <x-site.tech-chip :name="$t->name" />
                @endforeach
            </div>
            <span class="mt-auto inline-flex items-center gap-1 pt-5 font-mono text-xs tracking-[0.14em] text-accent-fg uppercase">
                {{ __('site.cta.view_project') }}
                <x-heroicon-o-arrow-right class="size-4 transition group-hover:translate-x-1" aria-hidden="true" />
            </span>
        </div>
    </div>
</article>
