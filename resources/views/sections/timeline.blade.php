{{-- Custom timeline section, e.g. experience, education or certificates (Admin → Home sections → Timeline). --}}
{{-- v2: compact rows (period · title · place) like the reference's list; details open on click, the newest one starts open. --}}
@php
    $items = collect($section->items ?? [])->filter(fn ($i) => ($i['is_visible'] ?? true) && filled($i['title'] ?? null))->values();
@endphp
<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" />

        <div class="relative mt-10" data-timeline>
        {{-- Line fills with scroll progress (resources/js/ui/interactions.js) --}}
        <span class="absolute top-0 left-0 h-full w-0.5 -translate-x-1/4 origin-top bg-primary" data-timeline-line aria-hidden="true"></span>
        <ol class="relative space-y-3 border-l border-line pl-6 sm:pl-8" data-reveal-stagger>
            @foreach ($items as $item)
                @php
                    // One line per bullet in Admin; a single line stays a paragraph.
                    $lines = str($item['description'] ?? '')->explode("\n")->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
                    $hasDetails = $lines->isNotEmpty() || filled($item['url'] ?? null);
                @endphp
                <li class="relative">
                    <span class="absolute top-7 -left-[1.95rem] size-3 rounded-full bg-primary ring-4 ring-bg transition duration-500 sm:-left-[2.45rem]" data-timeline-dot aria-hidden="true"></span>
                    @if ($hasDetails)
                        <details class="card log-card timeline-item" @if ($loop->first) open @endif>
                            <summary class="timeline-row">
                                @include('sections.partials.timeline-row', ['item' => $item, 'toggle' => true])
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
                                        {{ $item['link_label'] ?? 'View' }} <x-heroicon-o-arrow-top-right-on-square class="size-4" aria-hidden="true" />
                                    </a>
                                @endif
                            </div>
                        </details>
                    @else
                        <div class="card log-card">
                            <div class="timeline-row">
                                @include('sections.partials.timeline-row', ['item' => $item, 'toggle' => false])
                            </div>
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
        </div>
    </div>
</section>
