{{-- One timeline row: period · title + place · open/close sign (resources/views/sections/timeline.blade.php). --}}
<p class="eyebrow">{{ $item['period'] ?? '' }}</p>
<div>
    <h3 class="item-title">{{ $item['title'] }}</h3>
    @if (filled($item['subtitle'] ?? null))
        <p class="mt-1 font-item text-base font-light text-fg/80">{{ $item['subtitle'] }}</p>
    @endif
</div>
@if ($toggle)
    <span class="timeline-sign grid size-10 place-items-center rounded-sm border border-line text-xl leading-none max-md:hidden" aria-hidden="true">+</span>
@endif
