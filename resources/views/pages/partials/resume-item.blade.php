{{-- One timeline item on the résumé (pages/resume): title, period, place and its points. --}}
@php
    // One line per bullet in Admin, as on the home page (sections/timeline).
    $lines = str($item['description'] ?? '')->explode("\n")->map(fn ($l) => trim(ltrim(trim($l), '-•*')))->filter()->values();
    // The kind (Education, Certifications, Awards) is left out where it only repeats the section heading.
    $kind = filled($item['kind'] ?? null) ? __('site.timeline.kinds.'.$item['kind']) : null;
    $kind = $kind && mb_strtolower($kind) !== mb_strtolower($under ?? '') ? $kind : null;
    $place = collect([$kind, $item['subtitle'] ?? null])->filter()->join(' · ');
@endphp
<div class="mt-1.5 break-inside-avoid">
    <div class="flex items-baseline justify-between gap-x-4">
        <h3 class="text-[13px] font-bold">{{ $item['title'] }}</h3>
        <span class="shrink-0 font-mono text-[11px] text-muted">{{ $item['period'] ?? '' }}</span>
    </div>
    @if ($place)
        <p class="text-muted">{{ $place }}</p>
    @endif
    @if ($lines->count() === 1)
        <p class="mt-0.5">{{ $lines->first() }}</p>
    @elseif ($lines->isNotEmpty())
        <ul class="mt-0.5 list-disc space-y-0.5 pl-4">
            @foreach ($lines as $line)
                <li>{{ $line }}</li>
            @endforeach
        </ul>
    @endif
</div>
