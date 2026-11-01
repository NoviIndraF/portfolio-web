{{-- Logo strip: companies or clients as a scrolling row (Admin → Home sections → Logo strip). --}}
@php
    $items = $section->visibleItems();
@endphp
@if ($items->isNotEmpty())
    <section id="{{ $section->anchor }}" class="band-inverse py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
        <div class="container-site">
            <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" />
        </div>

        {{-- Screen readers and keyboard users get this list (with the links); the moving strip is decorative.
             It becomes visible while one of its links has focus. --}}
        <ul class="sr-only focus-within:not-sr-only focus-within:mx-auto focus-within:max-w-[120rem] focus-within:px-4 sm:focus-within:px-8 lg:focus-within:px-10 focus-within:mt-6 focus-within:flex focus-within:flex-wrap focus-within:gap-x-6 focus-within:gap-y-2">
            @foreach ($items as $item)
                <li>
                    @if (filled($item['url'] ?? null))
                        <a href="{{ $item['url'] }}" target="_blank" rel="noopener" class="underline">{{ $item['name'] }}</a>
                    @else
                        {{ $item['name'] }}
                    @endif
                </li>
            @endforeach
        </ul>
        <div class="relative mt-10">
        <x-site.marquee-toggle />
        <div class="marquee border-y border-line py-8" aria-hidden="true">
            <div class="marquee-track items-center">
                @foreach ([1, 2] as $copy)
                    @foreach ($items->count() < 6 ? $items->concat($items) : $items as $item)
                        @php($tag = filled($item['url'] ?? null) ? 'a' : 'span')
                        <{{ $tag }} @if ($tag === 'a') href="{{ $item['url'] }}" target="_blank" rel="noopener" tabindex="-1" @endif
                            class="logo-item flex h-12 shrink-0 items-center px-8 sm:px-12">
                            @if ($src = \App\Support\Img::url($item['logo'] ?? null))
                                <img src="{{ $src }}" alt="" loading="lazy" class="h-full w-auto max-w-40 object-contain">
                            @else
                                <span class="font-item text-2xl font-black whitespace-nowrap">{{ $item['name'] }}</span>
                            @endif
                        </{{ $tag }}>
                    @endforeach
                @endforeach
            </div>
        </div>
        </div>
    </section>
@endif
