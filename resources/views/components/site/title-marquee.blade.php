@props(['text'])
{{-- A section title running across the screen in big outlined / accent words, like the reference's "FEATURED WORK".
     Decorative: the real heading stays in the section heading for screen readers. 8 copies so -50% loops seamlessly. --}}
<div {{ $attributes->class('relative') }}>
    <x-site.marquee-toggle />
    <div class="marquee" aria-hidden="true">
        <div class="marquee-track">
            @foreach (range(1, 8) as $i)
                <span @class(['display flex items-center gap-[0.25em] pr-[0.25em] text-[clamp(4.5rem,13vw,13rem)] leading-none whitespace-nowrap', $i % 2 ? 'text-outline' : 'text-accent-fg'])>
                    {{ $text }} <span class="text-[0.35em] text-accent-fg">&#10022;</span>
                </span>
            @endforeach
        </div>
    </div>
</div>
