@props(['name', 'icon' => null])
@php
    $svg = \App\Support\Icon::svg(\App\Support\Icon::slugFor($name, $icon), 'size-3.5 shrink-0');
@endphp
<span {{ $attributes->class('chip') }}>
    @if ($svg)
        {{ $svg }}
    @else
        <span class="size-1.5 rounded-full bg-accent" aria-hidden="true"></span>
    @endif
    {{ $name }}
</span>
