@props(['eyebrow' => null, 'title', 'intro' => null, 'id' => null])
<div {{ $attributes->class('max-w-2xl') }} data-reveal>
    @if ($eyebrow)
        <p class="eyebrow">{{ $eyebrow }}</p>
    @endif
    <h2 @if ($id) id="{{ $id }}" @endif class="mt-2 text-3xl font-extrabold tracking-tight text-balance sm:text-4xl">{{ $title }}</h2>
    @if ($intro)
        <p class="mt-3 text-base text-muted text-pretty">{{ $intro }}</p>
    @endif
</div>
