{{-- One timeline row: period · title · place · open/close sign (resources/views/sections/timeline.blade.php).
     Only <span>, <img> and <h3> children: it sits inside <summary>, which allows phrasing and heading content only. --}}
<span class="timeline-period eyebrow">{{ $item['period'] ?? '' }}</span>
<h3 class="timeline-title item-title">{{ $item['title'] }}</h3>
@if (filled($item['subtitle'] ?? null))
    <span class="timeline-place inline-flex flex-wrap items-center gap-x-3 gap-y-1 font-item text-base font-light text-fg/80">
        {{-- Company logo, taken from the logo strip when its name matches (resources/views/sections/timeline.blade.php) --}}
        @if ($logo ?? null)
            <span class="inline-flex rounded-sm bg-white px-2 py-1"><img src="{{ $logo }}" alt="" loading="lazy" class="h-4 w-auto max-w-24 object-contain"></span>
        @endif
        <span>{{ $item['subtitle'] }}</span>
    </span>
@endif
@if ($toggle)
    <span class="timeline-sign grid size-10 place-items-center rounded-sm border border-line text-xl leading-none max-md:hidden" aria-hidden="true">+</span>
@endif
