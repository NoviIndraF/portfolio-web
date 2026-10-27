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
            <div class="flex items-center gap-3">
                @if ($photo = $profile->photoUrl())
                    <img src="{{ \App\Support\Img::small($profile->photo) }}" alt="" width="48" height="48" class="size-12 rounded-full object-cover ring-2 ring-primary/50" fetchpriority="high">
                @endif
                <p class="text-sm font-medium text-muted">{{ $profile->name }}</p>
            </div>

            <h1 id="hero-title" class="mt-5 text-4xl leading-[1.05] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl">
                {{ $role }}
            </h1>

            @if ($highlights->isNotEmpty())
                <ul class="mt-4 flex flex-wrap gap-2">
                    @foreach ($highlights as $h)
                        <li class="chip border-primary/30 text-sm">{{ $h }}</li>
                    @endforeach
                </ul>
            @endif

            @if ($profile->bio)
                <p class="mt-5 max-w-xl text-base text-muted text-pretty sm:text-lg">{{ $intro }}</p>
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
                    {{ __('site.cta.view_projects') }} <x-heroicon-o-arrow-right class="size-4 sm:hidden" aria-hidden="true" />
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

        <div class="flex flex-col items-center lg:items-end">
            <x-site.phone scene="hero" :screens="$reel" width="min(16rem, 62vw)" class="lg:mr-6" data-hero-phone :data-web-screens="$webScreens->isNotEmpty() ? $webScreens->toJson() : null" />
            @if ($reelLabels->isNotEmpty())
                <a href="{{ $reelLabels[0]['href'] }}" class="group relative z-10 mt-3 inline-flex items-center gap-1.5 rounded-full border border-line bg-surface/80 px-4 py-2 text-sm backdrop-blur transition hover:border-primary/60 lg:mr-6"
                   data-hero-label data-items="{{ $reelLabels->toJson() }}">
                    <span class="text-muted">{{ __('site.hero.now_showing') }}:</span>
                    <span class="font-semibold" data-hero-label-title aria-live="polite">{{ $reelLabels[0]['title'] }}</span>
                    <x-heroicon-o-arrow-right class="size-4 text-primary-fg transition group-hover:translate-x-0.5" aria-hidden="true" />
                </a>
            @endif
        </div>
    </div>
</section>
