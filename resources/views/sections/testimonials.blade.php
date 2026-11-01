{{-- Testimonials: recommendations from managers, colleagues or clients (Admin → Home sections → Testimonials). --}}
@php
    $items = $section->visibleItems();
@endphp
@if ($items->isNotEmpty())
    <section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
        <div class="container-site">
            <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" />

            <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-reveal-stagger>
                @foreach ($items as $item)
                    <figure class="card log-card flex flex-col p-6 sm:p-8">
                        <span class="font-display text-6xl leading-none text-accent-fg" aria-hidden="true">&ldquo;</span>
                        <blockquote class="mt-2 flex-1 font-item text-lg font-light text-pretty">{{ $item['quote'] }}</blockquote>
                        <figcaption class="mt-6 border-t border-line pt-4">
                            <p class="font-item font-black">
                                @if (filled($item['url'] ?? null))
                                    <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="hover:underline">{{ $item['name'] }}</a>
                                @else
                                    {{ $item['name'] }}
                                @endif
                            </p>
                            @if (filled($item['role'] ?? null))
                                <p class="eyebrow mt-1">{{ $item['role'] }}</p>
                            @endif
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </div>
    </section>
@endif
