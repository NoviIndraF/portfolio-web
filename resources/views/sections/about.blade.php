<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site grid items-center gap-10 lg:grid-cols-[0.8fr_1.2fr]">
        @php($gallery = $profile->photos()->visible()->get())
        @if ($gallery->isNotEmpty())
            {{-- Personal gallery (Admin → Profile → Gallery): swipe/drag rail, dots, auto-advance (resources/js/ui/interactions.js), opens the lightbox --}}
            <div class="relative mx-auto w-full max-w-sm" data-reveal>
                <div class="absolute -inset-3 -z-10 rounded-[2rem] bg-[conic-gradient(from_200deg,var(--c-primary),transparent_40%,var(--c-primary))] opacity-30 blur-xl" aria-hidden="true"></div>
                <div id="about-photos" class="flex snap-x snap-mandatory overflow-x-auto rounded-[1.75rem] border border-line [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                     data-rail data-photo-carousel aria-roledescription="carousel" aria-label="{{ __('site.about.gallery') }}">
                    @foreach ($gallery as $p)
                        <a href="{{ $p->url() }}" target="_blank" rel="noopener" class="relative block w-full shrink-0 snap-center"
                           data-lightbox-item data-src="{{ $p->url() }}" data-caption="{{ $p->caption }}" data-w="{{ $p->width }}" data-h="{{ $p->height }}"
                           aria-roledescription="slide" aria-label="{{ $loop->iteration }} / {{ $loop->count }}">
                            <img src="{{ $p->url() }}" alt="{{ $p->caption ?: $profile->name }}" loading="lazy" decoding="async" draggable="false"
                                 @if ($srcset = $p->srcset()) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 24rem, 90vw" @endif
                                 @if ($p->width) width="{{ $p->width }}" height="{{ $p->height }}" @endif
                                 class="aspect-[4/5] w-full object-cover">
                            @if ($p->caption)
                                <span class="absolute bottom-3 left-3 max-w-[calc(100%-1.5rem)] truncate rounded-full bg-bg/75 px-3 py-1.5 text-xs text-fg backdrop-blur">{{ $p->caption }}</span>
                            @endif
                        </a>
                    @endforeach
                </div>
                @if ($gallery->count() > 1)
                    <div class="mt-3 flex items-center justify-between">
                        <div class="flex flex-wrap">
                            @foreach ($gallery as $p)
                                <button type="button" class="group grid size-6 place-items-center" data-photo-dot aria-label="{{ __('site.about.go_to_photo', ['n' => $loop->iteration]) }}">
                                    <span class="h-1.5 w-1.5 rounded-full bg-fg/30 transition-all group-hover:bg-fg/60 group-aria-[current=true]:w-5 group-aria-[current=true]:bg-primary"></span>
                                </button>
                            @endforeach
                        </div>
                        <div class="flex gap-2">
                            <button type="button" class="btn-ghost size-9 min-h-9 px-0" data-rail-prev="about-photos" aria-label="{{ __('site.lightbox.prev') }}">
                                <x-heroicon-o-chevron-left class="size-4" aria-hidden="true" />
                            </button>
                            <button type="button" class="btn-ghost size-9 min-h-9 px-0" data-rail-next="about-photos" aria-label="{{ __('site.lightbox.next') }}">
                                <x-heroicon-o-chevron-right class="size-4" aria-hidden="true" />
                            </button>
                        </div>
                    </div>
                @endif
            </div>
            <x-site.lightbox />
        @elseif ($photo = $profile->photoUrl())
            <div class="relative mx-auto w-full max-w-sm" data-reveal>
                <div class="absolute -inset-3 -z-10 rounded-[2rem] bg-[conic-gradient(from_200deg,var(--c-primary),transparent_40%,var(--c-primary))] opacity-30 blur-xl" aria-hidden="true"></div>
                <img src="{{ $photo }}" alt="{{ $profile->name }}" loading="lazy" decoding="async"
                     @if ($srcset = \App\Support\Img::srcset($profile->photo)) srcset="{{ $srcset }}" sizes="(min-width: 1024px) 24rem, 90vw" @endif
                     class="aspect-[4/5] w-full rounded-[1.75rem] border border-line object-cover">
            </div>
        @endif
        <div>
            <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" />
            <p class="mt-5 text-base leading-relaxed text-muted text-pretty sm:text-lg">{{ $profile->bio }}</p>
            <div class="mt-7 flex flex-wrap gap-3">
                @if ($cv = $profile->cvUrl())
                    <a href="{{ $cv }}" download class="btn-primary">
                        <x-heroicon-o-arrow-down-tray class="size-5" aria-hidden="true" />
                        {{ __('site.cta.download_cv') }}
                    </a>
                @endif
                <a href="{{ ($c = $sections->firstWhere('type', 'contact')) ? '#'.$c->anchor : 'mailto:'.$profile->email }}" class="{{ $cv ? 'btn-ghost' : 'btn-primary' }}">{{ __('site.cta.contact_me') }}</a>
            </div>
        </div>
    </div>
</section>
