@props(['project'])
@php
    $shot = $project->screenshots->first();
    $cert = $project->certificates->first();
@endphp
<article {{ $attributes->class('group relative w-[82vw] max-w-[22rem] shrink-0 snap-start sm:w-[22rem]') }}
         style="--accent: {{ $project->accent_color }}" data-tilt data-project-card>
    <div class="card relative flex h-full flex-col overflow-hidden transition duration-300 group-hover:border-accent/60">
        {{-- Accent glow --}}
        <div class="pointer-events-none absolute inset-x-0 top-0 h-56 bg-[radial-gradient(60%_80%_at_50%_0%,color-mix(in_oklab,var(--accent,var(--c-primary))_40%,transparent),transparent)]" aria-hidden="true"></div>

        <div class="relative flex h-64 items-end justify-center overflow-hidden pt-6">
            <x-site.phone :project="$project" :interactive="false" width="9.5rem"
                          :screens="$shot ? [$shot->screen($project->title)] : []"
                          class="translate-y-16 transition duration-500 group-hover:translate-y-12" />
            @if (! $shot && $cert)
                <span class="absolute top-4 right-4 chip border-accent/40 bg-bg/70 backdrop-blur">
                    <x-heroicon-s-check-badge class="size-4 text-accent-fg" aria-hidden="true" /> HKI
                </span>
            @endif
        </div>

        <div class="relative flex flex-1 flex-col border-t border-line bg-surface p-5">
            <p class="text-xs font-semibold text-accent-fg">{{ __('site.project.types.'.$project->type) }}</p>
            <h3 class="mt-1 text-xl font-bold">
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
            <span class="mt-auto inline-flex items-center gap-1 pt-5 text-sm font-semibold text-accent-fg">
                {{ __('site.cta.view_project') }}
                <x-heroicon-o-arrow-right class="size-4 transition group-hover:translate-x-1" aria-hidden="true" />
            </span>
        </div>
    </div>
</article>
