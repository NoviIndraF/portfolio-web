@props(['eyebrow' => null, 'title', 'intro' => null, 'id' => null, 'hideTitle' => false, 'size' => 'xl', 'center' => false, 'oneLine' => false])
{{-- v2: the section number comes from a CSS counter (resources/css/app.css, .section-index). --}}
{{-- size: "xl" (giant, for the big moments: projects, contact) or "md" (calmer, so not every section opens the same way). --}}
<div {{ $attributes->class(['section-heading max-w-5xl', 'mx-auto text-center' => $center]) }} data-reveal>
    <p @class(['eyebrow eyebrow-lg flex items-center gap-3', 'justify-center' => $center])>
        <span class="section-index" aria-hidden="true"></span>
        <span class="h-px w-8 bg-line" aria-hidden="true"></span>
        @if ($eyebrow)
            <span>{{ $eyebrow }}</span>
        @endif
    </p>
    {{-- oneLine: the title never wraps; on narrow screens it shrinks to fit instead.
         shortcut: assumes at most 0.45em per character (the condensed display font, uppercase); measure the text if a title still overflows. --}}
    {{-- hideTitle: a title marquee shows it instead (x-site.title-marquee); the heading stays for screen readers. --}}
    <h2 @if ($id) id="{{ $id }}" @endif
        @if ($oneLine) style="font-size: min(clamp(3.25rem, 9vw, 8.5rem), calc((100vw - 2.5rem) / {{ round(mb_strlen($title) * 0.45, 2) }}))" @endif
        @class([
        'display mt-3 text-balance',
        'whitespace-nowrap' => $oneLine,
        'text-[clamp(3.25rem,9vw,8.5rem)]' => $size === 'xl',
        'text-[clamp(2.5rem,5.5vw,5rem)]' => $size === 'md',
        'sr-only' => $hideTitle,
    ])>
        <span class="title-reveal" data-title-reveal><span>{{ $title }}</span></span>
    </h2>
    @if ($intro)
        <p @class(['mt-4 max-w-2xl text-base text-muted text-pretty', 'mx-auto' => $center])>{{ $intro }}</p>
    @endif
</div>
