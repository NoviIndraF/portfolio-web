@props(['eyebrow' => null, 'title', 'intro' => null, 'id' => null])
{{-- v2: the section number comes from a CSS counter (resources/css/app.css, .section-index). --}}
<div {{ $attributes->class('section-heading max-w-3xl') }} data-reveal>
    <p class="eyebrow flex items-center gap-3">
        <span class="section-index" aria-hidden="true"></span>
        <span class="h-px w-8 bg-line" aria-hidden="true"></span>
        @if ($eyebrow)
            <span>{{ $eyebrow }}</span>
        @endif
    </p>
    <h2 @if ($id) id="{{ $id }}" @endif class="display mt-3 text-5xl text-balance sm:text-6xl lg:text-7xl">{{ $title }}</h2>
    @if ($intro)
        <p class="mt-4 text-base text-muted text-pretty">{{ $intro }}</p>
    @endif
</div>
