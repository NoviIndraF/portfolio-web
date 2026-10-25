@php
    $screens = $project->images->where('kind', 'screenshot')->values();
    $certificates = $project->images->where('kind', 'certificate')->values();
    // Link preview: first app screen; projects without screens use the site default image.
    $shareImage = $screens->first() ?? $project->images->whereIn('kind', ['web', 'admin'])->first();
    $shareUrl = $shareImage ? asset('storage/'.($shareImage->original_path ?: $shareImage->path)) : null;
    $rec = $project->recognition;
@endphp
<x-layouts.site :title="$project->title" :description="$project->impact_line ?: $project->short_description"
                :image="$shareUrl" :accent="$project->accent_color">

    {{-- Header + phone viewer --}}
    <section class="relative isolate overflow-hidden" aria-labelledby="project-title">
        <div class="absolute inset-0 -z-10 bg-[radial-gradient(60%_60%_at_75%_30%,color-mix(in_oklab,var(--accent,var(--c-primary))_28%,transparent),transparent)]" aria-hidden="true"></div>

        <div class="container-site pt-6">
            <a href="{{ route('home') }}#projects" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-fg">
                <x-heroicon-o-arrow-left class="size-4" aria-hidden="true" /> {{ __('site.project.back') }}
            </a>
        </div>

        <div class="container-site grid items-center gap-12 pt-8 pb-16 lg:grid-cols-[1.1fr_0.9fr] lg:pb-24">
            <div>
                <p class="eyebrow">{{ $project->typeLabel() }}@if ($project->year) · {{ $project->year }}@endif</p>
                <h1 id="project-title" class="mt-2 text-4xl font-extrabold tracking-tight text-balance sm:text-5xl" style="view-transition-name: project-title" data-project-title>{{ $project->title }}</h1>
                @if ($project->impact_line)
                    <p class="mt-4 max-w-xl text-lg text-fg/90 text-pretty">{{ $project->impact_line }}</p>
                @endif

                @if ($rec)
                    <div class="mt-6 flex max-w-xl items-start gap-4 rounded-2xl border border-accent/40 bg-accent/10 p-4">
                        <x-heroicon-s-check-badge class="size-8 shrink-0 text-accent-fg" aria-hidden="true" />
                        <div class="min-w-0 text-sm">
                            <p class="font-bold">{{ __('site.project.recognition') }}</p>
                            <p class="mt-1 text-muted">
                                {{ collect([
                                    $rec['type'] ?? null,
                                    ! empty($rec['holder']) ? __('site.project.holder').': '.$rec['holder'] : null,
                                    ! empty($rec['published']) ? __('site.project.published').': '.$rec['published'] : null,
                                ])->filter()->implode(' · ') }}
                            </p>
                            @if ($certificates->isNotEmpty())
                                <a href="{{ $certificates->first()->url() }}" target="_blank" rel="noopener"
                                   class="mt-2 inline-flex items-center gap-1 font-semibold text-accent-fg hover:underline"
                                   data-lightbox-open="certificate">
                                    {{ __('site.project.view_certificate') }} <x-heroicon-o-arrow-right class="size-4" aria-hidden="true" />
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @if ($project->links->isNotEmpty())
                    <div class="mt-7 flex flex-wrap gap-3">
                        @foreach ($project->links as $i => $link)
                            <a href="{{ $link->url }}" target="_blank" rel="noopener" class="{{ $i === 0 ? 'btn-primary bg-accent' : 'btn-ghost' }}">
                                {{ $link->label }}
                                <x-heroicon-o-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>

            <div class="flex flex-col items-center">
                <x-site.phone :project="$project" width="min(16.5rem, 64vw)" transition-name="project-phone"
                              :screens="$screens->map(fn ($s) => $s->screen($project->title))" />
                @if ($screens->count() > 1)
                    <p class="mt-3 text-xs text-muted">{{ __('site.project.swipe_hint') }}</p>
                @endif
            </div>
        </div>
    </section>

    @php
        $pageSections = collect([
            'overview-title' => __('site.project.overview'),
            'features-title' => $project->features->isNotEmpty() ? __('site.project.features') : null,
            'tech-title' => $project->tech->isNotEmpty() ? __('site.project.tech') : null,
            'links-title' => $project->links->isNotEmpty() ? __('site.project.links') : null,
            'gallery-title' => $project->images->isNotEmpty() ? __('site.project.gallery') : null,
        ])->filter();
    @endphp
    <nav class="sticky top-16 z-30 border-y border-line/60 bg-bg/85 backdrop-blur-md" aria-label="{{ __('site.project.sections_nav') }}" data-subnav>
        <ul class="container-site no-scrollbar flex gap-1 overflow-x-auto py-2" data-scrollspy>
            @foreach ($pageSections as $id => $label)
                <li class="shrink-0"><a href="#{{ $id }}" class="block rounded-full px-3 py-1.5 text-sm font-medium text-muted transition hover:text-fg aria-[current=true]:bg-accent/15 aria-[current=true]:text-accent-fg">{{ $label }}</a></li>
            @endforeach
        </ul>
    </nav>

    <div class="container-site space-y-16 pt-12 pb-16 sm:space-y-20 sm:pb-24">
        {{-- 1. Overview: problem / solution --}}
        <section aria-labelledby="overview-title" data-reveal>
            <h2 id="overview-title" class="scroll-mt-32 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.project.overview') }}</h2>
            @if ($project->problem || $project->solution)
                <div class="mt-6 grid gap-4 md:grid-cols-2">
                    @foreach (['problem' => $project->problem, 'solution' => $project->solution] as $key => $text)
                        @if ($text)
                            <div class="card p-6">
                                <p class="eyebrow">{{ __('site.project.'.$key) }}</p>
                                <p class="mt-3 text-muted text-pretty">{{ $text }}</p>
                            </div>
                        @endif
                    @endforeach
                </div>
            @else
                <p class="mt-4 max-w-3xl text-lg text-muted text-pretty">{{ $project->short_description }}</p>
            @endif
        </section>

        {{-- 2. Features --}}
        @if ($project->features->isNotEmpty())
            <section aria-labelledby="features-title">
                <h2 id="features-title" class="scroll-mt-32 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.project.features') }}</h2>
                @php
                    $featureCols = [1 => 'lg:grid-cols-2', 2 => 'lg:grid-cols-2', 3 => 'lg:grid-cols-3'][$project->features->count()] ?? 'lg:grid-cols-4';
                @endphp
                <ul class="mt-6 grid gap-4 sm:grid-cols-2 {{ $featureCols }}" data-features data-reveal-stagger>
                    @foreach ($project->features as $i => $feature)
                        @php
                            $screenIndex = $feature->project_image_id ? $screens->search(fn ($s) => $s->id === $feature->project_image_id) : false;
                        @endphp
                        <li class="card group relative overflow-hidden p-6 transition hover:-translate-y-1 hover:border-accent/50" data-feature-card
                            @if ($screenIndex !== false) data-screen-index="{{ $screenIndex }}" @endif>
                            <span class="grid size-10 place-items-center rounded-xl bg-accent/15 text-sm font-extrabold text-accent-fg">
                                @php
                                    try { $featureIcon = $feature->icon ? svg($feature->icon, 'size-5')->toHtml() : null; }
                                    catch (\Throwable) { $featureIcon = null; } // unknown icon name: fall back to the number
                                @endphp
                                @if ($featureIcon)
                                    {!! $featureIcon !!}
                                @else
                                    {{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}
                                @endif
                            </span>
                            <h3 class="mt-4 font-bold">{{ $feature->title }}</h3>
                            @if ($feature->description)
                                <p class="mt-2 text-sm text-muted text-pretty">{{ $feature->description }}</p>
                            @endif
                            @if ($screenIndex !== false)
                                @php
                                    $shot = $screens[$screenIndex];
                                @endphp
                                <button type="button" class="mt-4 flex w-full items-center gap-3 rounded-xl border border-line bg-bg/50 p-2 text-left text-sm font-semibold text-accent-fg transition hover:border-accent/60 after:absolute after:inset-0" data-show-screen>
                                    <img src="{{ \App\Support\Img::small($shot->path) }}" alt="" loading="lazy" decoding="async" class="h-14 w-8 rounded-md object-cover object-top">
                                    {{ __('site.project.see_screen') }}
                                </button>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- 3. Tech --}}
        @if ($project->tech->isNotEmpty())
            <section aria-labelledby="tech-title">
                <h2 id="tech-title" class="scroll-mt-32 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.project.tech') }}</h2>
                <ul class="mt-6 flex flex-wrap gap-2" data-reveal-stagger>
                    @foreach ($project->tech as $t)
                        <li><x-site.tech-chip :name="$t->name" class="px-3.5 py-1.5 text-sm" /></li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- 4. Links (only when present) --}}
        @if ($project->links->isNotEmpty())
            <section aria-labelledby="links-title">
                <h2 id="links-title" class="scroll-mt-32 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.project.links') }}</h2>
                <ul class="mt-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-3" data-reveal-stagger>
                    @foreach ($project->links as $link)
                        <li>
                            <a href="{{ $link->url }}" target="_blank" rel="noopener"
                               class="flex items-center justify-between gap-3 rounded-2xl border border-line bg-surface px-5 py-4 transition hover:border-accent/60">
                                <span class="min-w-0">
                                    <span class="block font-semibold">{{ $link->label }}</span>
                                    <span class="block truncate text-xs text-muted">{{ str($link->url)->after('://')->rtrim('/') }}</span>
                                </span>
                                <x-heroicon-o-arrow-top-right-on-square class="size-5 shrink-0 text-accent-fg" aria-hidden="true" />
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- 5. Gallery --}}
        @if ($project->images->isNotEmpty())
            <section aria-labelledby="gallery-title" data-reveal>
                <h2 id="gallery-title" class="scroll-mt-32 text-2xl font-extrabold tracking-tight sm:text-3xl">{{ __('site.project.gallery') }}</h2>
                <div class="mt-6">
                    <x-site.gallery :project="$project" />
                </div>
            </section>
        @endif

        {{-- Prev / next + contact --}}
        <nav class="grid gap-3 border-t border-line pt-10 sm:grid-cols-2" aria-label="Projects">
            @if ($prev)
                <a href="{{ route('projects.show', $prev) }}" class="card flex items-center gap-3 p-5 transition hover:border-fg/30" style="--accent: {{ $prev->accent_color }}">
                    <x-heroicon-o-arrow-left class="size-5 text-accent-fg" aria-hidden="true" />
                    <span><span class="block text-xs text-muted">{{ __('site.project.prev') }}</span><span class="font-semibold">{{ $prev->title }}</span></span>
                </a>
            @else
                <span class="hidden sm:block"></span>
            @endif
            @if ($next)
                <a href="{{ route('projects.show', $next) }}" class="card flex items-center justify-end gap-3 p-5 text-right transition hover:border-fg/30" style="--accent: {{ $next->accent_color }}">
                    <span><span class="block text-xs text-muted">{{ __('site.project.next') }}</span><span class="font-semibold">{{ $next->title }}</span></span>
                    <x-heroicon-o-arrow-right class="size-5 text-accent-fg" aria-hidden="true" />
                </a>
            @endif
        </nav>
    </div>

    @php
        $contactSection = app(\App\Support\Site::class)->sections()->firstWhere('type', 'contact');
    @endphp
    @if ($contactSection)
        @include('sections.contact', ['section' => $contactSection])
    @endif
    <x-site.lightbox />
</x-layouts.site>
