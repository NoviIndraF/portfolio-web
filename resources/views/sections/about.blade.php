<section id="about" class="py-16 sm:py-24" aria-labelledby="about-title">
    <div class="container-site grid items-center gap-10 lg:grid-cols-[0.8fr_1.2fr]">
        @if ($photo = $profile->photoUrl())
            <div class="relative mx-auto w-full max-w-sm" data-reveal>
                <div class="absolute -inset-3 -z-10 rounded-[2rem] bg-[conic-gradient(from_200deg,var(--c-primary),transparent_40%,var(--c-primary))] opacity-30 blur-xl" aria-hidden="true"></div>
                <img src="{{ $photo }}" alt="{{ $profile->name }}" loading="lazy" decoding="async"
                     @if ($srcset = \App\Support\Img::srcset($profile->photo)) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 24rem, 90vw" @endif
                     class="aspect-[4/5] w-full rounded-[1.75rem] border border-line object-cover">
            </div>
        @endif
        <div>
            <x-site.section-heading id="about-title" :eyebrow="__('site.about.eyebrow')" :title="__('site.about.title')" />
            <p class="mt-5 text-base leading-relaxed text-muted text-pretty sm:text-lg">{{ $profile->bio }}</p>
            <div class="mt-7 flex flex-wrap gap-3">
                @if ($cv = $profile->cvUrl())
                    <a href="{{ $cv }}" download class="btn-primary">
                        <x-heroicon-o-arrow-down-tray class="size-5" aria-hidden="true" />
                        {{ __('site.cta.download_cv') }}
                    </a>
                @endif
                <a href="#contact" class="{{ $cv ? 'btn-ghost' : 'btn-primary' }}">{{ __('site.cta.contact_me') }}</a>
            </div>
        </div>
    </div>
</section>
