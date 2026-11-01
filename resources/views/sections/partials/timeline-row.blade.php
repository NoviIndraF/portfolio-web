{{-- One timeline row: period · title · place · open/close sign (resources/views/sections/timeline.blade.php).
     Only <span> and <h3> children: it sits inside <summary>, which allows phrasing and heading content only. --}}
<span class="timeline-period eyebrow">{{ $item['period'] ?? '' }}</span>
<h3 class="timeline-title item-title">{{ $item['title'] }}</h3>
@if (filled($item['subtitle'] ?? null))
    <span class="timeline-place font-item text-base font-light text-fg/80">{{ $item['subtitle'] }}</span>
@endif
@if ($toggle)
    <span class="timeline-sign grid size-10 place-items-center rounded-sm border border-line text-xl leading-none max-md:hidden" aria-hidden="true">+</span>
@endif
