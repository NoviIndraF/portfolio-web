{{-- Custom timeline section, e.g. experience, education or certificates (Admin → Home sections → Timeline). --}}
{{-- v2: compact rows (period · title · place) like the reference's list; details open on click, the newest one starts open. --}}
@php
    $items = $section->visibleItems();
    // Nothing to open (no item has a description or link), e.g. education or certificates: short cards grouped by kind.
    $compact = $items->every(fn ($i) => blank($i['description'] ?? null) && blank($i['url'] ?? null));
    // Company logos come from the logo strip (Admin → Home sections → Logo strip): upload once, shown in both places.
    $logos = collect(($sections ?? collect())->firstWhere('type', 'logos')?->visibleItems())
        ->filter(fn ($l) => filled($l['logo'] ?? null))
        ->mapWithKeys(fn ($l) => [mb_strtolower(trim($l['name'])) => \App\Support\Img::url($l['logo'])]);
    $logoFor = fn ($item) => $logos->get(mb_strtolower(trim(str($item['subtitle'] ?? '')->before('·'))));
    $kindIcons = ['education' => 'heroicon-o-academic-cap', 'certificate' => 'heroicon-o-check-badge', 'award' => 'heroicon-o-trophy'];
@endphp
<section id="{{ $section->anchor }}" @class(['py-16 sm:py-24', 'band-surface' => $compact]) aria-labelledby="{{ $section->anchor }}-title">
    {{-- Experience on wide screens: the title stays on the left while the timeline scrolls on the right. --}}
    <div @class(['container-site', 'xl:grid xl:grid-cols-[minmax(0,24rem)_1fr] xl:gap-16' => ! $compact])>
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" size="md"
            :class="$compact ? '' : 'xl:sticky xl:top-28 xl:self-start'" />

        @if ($compact)
            @php
                $groups = $items->groupBy(fn ($i) => $i['kind'] ?? '');
            @endphp
            <div @class(['mt-10 grid gap-x-6 gap-y-10', 'md:grid-cols-2 xl:grid-cols-3' => $groups->count() > 1])>
                @foreach ($groups as $kind => $group)
                    <div>
                        @if ($kind && isset($kindIcons[$kind]))
                            <p class="eyebrow eyebrow-lg flex items-center gap-2">
                                <x-dynamic-component :component="$kindIcons[$kind]" class="size-4" aria-hidden="true" />
                                {{ __('site.timeline.kinds.'.$kind) }}
                            </p>
                        @endif
                        <ul @class(['grid gap-3', 'mt-4' => $kind, 'sm:grid-cols-2 xl:grid-cols-3' => $groups->count() === 1]) data-reveal-stagger>
                            @foreach ($group as $item)
                                <li class="card log-card flex h-full flex-col p-5 sm:p-6">
                                    <span class="flex items-center justify-between gap-3">
                                        <span class="eyebrow">{{ $item['period'] ?? '' }}</span>
                                        @if (isset($kindIcons[$kind]))
                                            <x-dynamic-component :component="$kindIcons[$kind]" class="size-6 shrink-0 text-accent-fg" aria-hidden="true" />
                                        @endif
                                    </span>
                                    <h3 class="mt-3 font-item text-xl leading-snug font-black text-balance sm:text-2xl">{{ $item['title'] }}</h3>
                                    @if (filled($item['subtitle'] ?? null))
                                        <p class="mt-auto pt-3 font-item text-sm font-light text-muted">{{ $item['subtitle'] }}</p>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endforeach
            </div>
        @else
        <div class="relative mt-10 xl:mt-0" data-timeline>
        {{-- Line fills with scroll progress (resources/js/ui/interactions.js) --}}
        <span class="absolute top-0 left-0 h-full w-0.5 -translate-x-1/4 origin-top bg-primary" data-timeline-line aria-hidden="true"></span>
        <ol class="relative space-y-3 border-l border-line pl-6 sm:pl-8" data-reveal-stagger>
            @foreach ($items as $item)
                @php
                    // One line per bullet in Admin; a single line stays a paragraph.
                    $lines = str($item['description'] ?? '')->explode("\n")->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
                    // A single paragraph of several sentences reads better as points (easier to scan, mostly on phones).
                    if ($lines->count() === 1) {
                        $lines = collect(preg_split('/(?<=[.!?])\s+(?=\p{Lu})/u', $lines->first()));
                    }
                    $hasDetails = $lines->isNotEmpty() || filled($item['url'] ?? null);
                @endphp
                <li class="relative">
                    <span class="absolute top-7 -left-[1.95rem] size-3 rounded-full bg-primary ring-4 ring-bg transition duration-500 sm:-left-[2.45rem]" data-timeline-dot aria-hidden="true"></span>
                    @if ($hasDetails)
                        <details class="card log-card timeline-item" @if ($loop->first) open @endif>
                            <summary class="timeline-row">
                                @include('sections.partials.timeline-row', ['item' => $item, 'toggle' => true, 'logo' => $logoFor($item)])
                            </summary>
                            <div class="px-5 pb-6 sm:px-6 md:pr-8 md:pb-8 md:pl-[calc(11rem+4rem)]">
                                @if ($lines->count() > 1)
                                    <ul class="max-w-[72ch] space-y-1.5 font-item text-sm font-light text-muted">
                                        @foreach ($lines as $line)
                                            <li class="relative pl-4 text-pretty before:absolute before:top-2 before:left-0 before:size-1.5 before:rounded-full before:bg-primary/60">{{ $line }}</li>
                                        @endforeach
                                    </ul>
                                @elseif ($lines->isNotEmpty())
                                    <p class="max-w-[72ch] font-item text-sm font-light text-muted text-pretty">{{ $lines->first() }}</p>
                                @endif
                                @if (filled($item['url'] ?? null))
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary-fg hover:underline">
                                        {{ $item['link_label'] ?? __('site.timeline.view') }} <x-heroicon-o-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                                    </a>
                                @endif
                            </div>
                        </details>
                    @else
                        <div class="card log-card">
                            <div class="timeline-row">
                                @include('sections.partials.timeline-row', ['item' => $item, 'toggle' => false, 'logo' => $logoFor($item)])
                            </div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
        </div>
        @endif
    </div>
</section>
