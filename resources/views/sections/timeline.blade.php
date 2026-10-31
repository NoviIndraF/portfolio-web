{{-- Custom timeline section, e.g. experience, education or certificates (Admin → Home sections → Timeline). --}}
@php
    $items = collect($section->items ?? [])->filter(fn ($i) => ($i['is_visible'] ?? true) && filled($i['title'] ?? null));
@endphp
<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" />

        <div class="relative mt-10" data-timeline>
        {{-- Line fills with scroll progress (resources/js/ui/interactions.js) --}}
        <span class="absolute top-0 left-0 h-full w-0.5 -translate-x-1/4 origin-top bg-primary" data-timeline-line aria-hidden="true"></span>
        <ol class="relative space-y-6 border-l border-line pl-6 sm:pl-8" data-reveal-stagger>
            @foreach ($items as $item)
                <li class="relative">
                    <span class="absolute top-2 -left-[1.95rem] size-3 rounded-full bg-primary ring-4 ring-bg transition duration-500 sm:-left-[2.45rem]" data-timeline-dot aria-hidden="true"></span>
                    <div class="card log-card grid gap-2 p-5 sm:p-6 md:grid-cols-[11rem_1fr] md:gap-8 md:p-8">
                        <p class="eyebrow md:pt-2">{{ $item['period'] ?? '' }}</p>
                        <div>
                        <h3 class="item-title">{{ $item['title'] }}</h3>
                        @if (filled($item['subtitle'] ?? null))
                            <p class="mt-1 font-item text-base font-light text-fg/80">{{ $item['subtitle'] }}</p>
                        @endif
                        @if (filled($item['description'] ?? null))
                            {{-- One line per bullet in Admin; a single line stays a paragraph. --}}
                            @php($lines = str($item['description'])->explode("\n")->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values())
                            @if ($lines->count() > 1)
                                <ul class="mt-3 max-w-[72ch] space-y-1.5 font-item text-sm font-light text-muted">
                                    @foreach ($lines as $line)
                                        <li class="relative pl-4 text-pretty before:absolute before:top-2 before:left-0 before:size-1.5 before:rounded-full before:bg-primary/60">{{ $line }}</li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="mt-2 max-w-[72ch] font-item text-sm font-light text-muted text-pretty">{{ $lines->first() }}</p>
                            @endif
                        @endif
                        @if (filled($item['url'] ?? null))
                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1 text-sm font-semibold text-primary-fg hover:underline">
                                {{ $item['link_label'] ?? 'View' }} <x-heroicon-o-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                            </a>
                        @endif
                        </div>
                    </div>
                </li>
            @endforeach
        </ol>
        </div>
    </div>
</section>
