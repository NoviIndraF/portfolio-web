{{-- Pauses / resumes every moving strip on the page (WCAG 2.2.2); the choice is remembered (resources/js/ui/v2.js). --}}
<button type="button" class="marquee-toggle absolute top-1/2 right-3 z-10 grid size-9 -translate-y-1/2 place-items-center rounded-sm border border-line bg-bg/85 text-muted backdrop-blur transition hover:border-accent-fg/60 hover:text-accent-fg"
        data-marquee-toggle aria-pressed="false" data-label-pause="{{ __('site.motion.pause') }}" data-label-play="{{ __('site.motion.play') }}"
        aria-label="{{ __('site.motion.pause') }}" title="{{ __('site.motion.pause') }}">
    <x-heroicon-s-pause class="icon-pause size-4" aria-hidden="true" />
    <x-heroicon-s-play class="icon-play size-4" aria-hidden="true" />
</button>
