@php
    $parts = str($profile->tagline)->explode('|')->map(fn ($p) => trim($p))->filter()->values();
    $role = $parts->first();
    $highlights = $parts->slice(1);
    $intro = str($profile->bio)->before('. ')->append('.')->toString();
    // One screen per project for the hero phone reel.
    $contactHref = ($c = $sections->firstWhere('type', 'contact')) ? '#'.$c->anchor : 'mailto:'.$profile->email;
    $projectsHref = ($p = $sections->firstWhere('type', 'projects')) ? '#'.$p->anchor : route('projects.index');
    $reelProjects = $projects->filter(fn ($p) => $p->screenshots->isNotEmpty())->values();
    $reel = $reelProjects->map(fn ($p) => $p->screenshots->first()->screen($p->title));
    // "Now showing" label under the phone follows the active screen (resources/js/ui/hero-label.js).
    $reelLabels = $reelProjects->map(fn ($p) => ['title' => $p->title, 'href' => route('projects.show', $p)]);
    // "Web" images (Admin → project images) rotate in the 3D browser window next to the phone.
    $webScreens = $projects->flatMap(fn ($p) => $p->webShots)->map(fn ($s) => $s->screen()['url'])->values();
@endphp
<section class="relative isolate overflow-hidden" aria-labelledby="hero-title">
    {{-- Background + 3D particles mount (stage 4) --}}
    <div class="absolute inset-0 -z-10 bg-[radial-gradient(70%_60%_at_80%_20%,color-mix(in_oklab,var(--c-primary)_22%,transparent),transparent),radial-gradient(50%_50%_at_0%_100%,color-mix(in_oklab,var(--c-primary)_12%,transparent),transparent)]" aria-hidden="true"></div>
    <div class="absolute inset-0 -z-10" data-scene="hero-particles" aria-hidden="true"></div>

    <div class="container-site grid items-center gap-12 pt-10 pb-16 sm:pt-14 lg:grid-cols-[1.15fr_0.85fr] lg:py-24">
        <div>
            <p class="eyebrow">{{ $role }}</p>

            {{-- v2: name in big condensed type, one word per line --}}
            <h1 id="hero-title" class="display mt-4 text-[clamp(4.25rem,15vw,8rem)] text-fg lg:text-[clamp(8rem,11.5vw,14rem)]">
                @foreach (str($profile->name)->explode(' ')->filter() as $word)
                    <span class="reveal-line"><span @class(['text-accent-fg' => $loop->index === 1]) style="--i: {{ $loop->index }}">{{ $word }}</span></span>
                @endforeach
            </h1>

            @if ($profile->bio)
                <p class="mt-6 max-w-xl text-base text-muted text-pretty sm:text-lg">{{ $intro }}</p>
            @endif

            <div class="mt-7 flex flex-wrap gap-3">
                @if ($cv = $profile->cvUrl())
                    <a href="{{ $cv }}" download class="btn-primary">
                        <x-heroicon-o-arrow-down-tray class="size-5" aria-hidden="true" />
                        {{ __('site.cta.download_cv') }}
                    </a>
                    <a href="{{ $contactHref }}" class="btn-ghost">
                        <x-heroicon-o-envelope class="size-5" aria-hidden="true" />
                        {{ __('site.cta.contact_me') }}
                    </a>
                @else
                    <a href="{{ $contactHref }}" class="btn-primary">
                        <x-heroicon-o-envelope class="size-5" aria-hidden="true" />
                        {{ __('site.cta.contact_me') }}
                    </a>
                @endif
                {{-- Phones: a text link, so the CTAs stay on one row --}}
                <a href="{{ $projectsHref }}" class="btn-ghost max-sm:border-transparent max-sm:bg-transparent max-sm:px-1 max-sm:text-primary-fg">
                    {{ __('site.cta.view_projects') }} <x-heroicon-o-arrow-right class="arrow-shift size-4 sm:hidden" aria-hidden="true" />
                </a>
            </div>

            @if ($profile->stats)
                <dl class="mt-8 grid max-w-lg grid-cols-3 gap-2 sm:mt-10 sm:gap-3">
                    @foreach ($profile->stats as $stat)
                        <div class="card flex flex-col px-3 py-2.5 sm:px-4 sm:py-3">
                            <dt class="text-xs text-muted">{{ $stat['label'] }}</dt>
                            <dd class="order-first text-2xl font-extrabold text-fg tabular-nums sm:text-3xl" data-countup>{{ $stat['value'] }}</dd>
                        </div>
                    @endforeach
                </dl>
            @endif
        </div>

        <div class="flex flex-col items-center">
            <x-site.phone scene="hero" :screens="$reel" width="min(16rem, 62vw)" data-hero-phone :data-web-screens="$webScreens->isNotEmpty() ? $webScreens->toJson() : null" />
            @if ($reelLabels->isNotEmpty())
                <a href="{{ $reelLabels[0]['href'] }}" class="group relative z-10 mt-3 inline-flex items-center gap-1.5 rounded-sm border border-line bg-surface/80 px-4 py-2 text-sm backdrop-blur transition hover:border-primary/60"
                   data-hero-label data-items="{{ $reelLabels->toJson() }}">
                    <span class="text-muted">{{ __('site.hero.now_showing') }}:</span>
                    <span class="font-semibold" data-hero-label-title aria-live="polite">{{ $reelLabels[0]['title'] }}</span>
                    <x-heroicon-o-arrow-right class="size-4 text-primary-fg transition group-hover:translate-x-0.5" aria-hidden="true" />
                </a>
            @endif
        </div>
    </div>

    {{-- v2: highlights from the tagline as a looping strip --}}
    @if ($highlights->isNotEmpty())
        <p class="sr-only">{{ $highlights->implode(', ') }}</p>
        <div class="marquee border-y border-line py-4" aria-hidden="true">
            <div class="marquee-track">
                @foreach ([1, 2] as $copy)
                    @foreach ($highlights->concat($highlights) as $h)
                        <span @class(['display flex items-center gap-[0.3em] pr-[0.3em] text-[clamp(3rem,8vw,8rem)]', $loop->even ? 'text-accent-fg' : 'text-outline'])>{{ $h }} <span class="text-[0.4em] text-accent-fg">&#10022;</span></span>
                    @endforeach
                @endforeach
            </div>
        </div>
    @endif

    <a href="{{ $projectsHref }}" class="container-site flex items-center gap-2 py-4 font-mono text-[11px] tracking-[0.2em] text-muted uppercase hover:text-fg max-lg:hidden">
        <span aria-hidden="true">&#9660;</span> {{ __('site.hero.scroll') }}
    </a>
</section>
