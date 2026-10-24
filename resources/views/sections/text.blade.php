{{-- Custom text section (Admin → Home sections → Text block). --}}
<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->eyebrow" :title="$section->title" :intro="$section->intro" />
        @if (filled($section->body))
            <div class="prose-site mt-6 max-w-3xl text-base leading-relaxed text-muted sm:text-lg" data-reveal>
                {!! str($section->body)->sanitizeHtml() !!}
            </div>
        @endif
    </div>
</section>
