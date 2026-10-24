{{-- Project gallery grouped by image kind. Each image opens in the lightbox (swipe, pinch, keyboard). --}}
@props(['project'])
@php
    $groups = $project->images->groupBy('kind')->sortBy(fn ($imgs, $kind) => array_search($kind, array_keys(\App\Models\ProjectImage::KINDS)));
@endphp
<div class="space-y-10" data-gallery>
    @foreach ($groups as $kind => $images)
        <div>
            <h3 class="text-sm font-semibold tracking-wide text-muted uppercase">{{ __('site.project.kinds.'.$kind) }}</h3>
            <ul class="mt-4 grid gap-3 {{ in_array($kind, ['screenshot', 'hardware', 'certificate']) ? 'grid-cols-2 sm:grid-cols-3 lg:grid-cols-5' : 'sm:grid-cols-2' }}">
                @foreach ($images as $img)
                    <li>
                        <a href="{{ $img->url() }}" target="_blank" rel="noopener" class="group block w-full overflow-hidden rounded-2xl border border-line bg-surface text-left transition hover:border-accent/60"
                                data-lightbox-item data-kind="{{ $kind }}" data-src="{{ $img->url() }}" data-caption="{{ $img->caption }}"
                                data-w="{{ $img->width }}" data-h="{{ $img->height }}">
                            <img src="{{ $img->url() }}" alt="{{ $project->title }} – {{ $img->caption }}" loading="lazy" decoding="async"
                                 @if ($srcset = $img->srcset()) srcset="{{ $srcset }}" sizes="{{ in_array($kind, ['screenshot', 'hardware', 'certificate']) ? '(min-width: 1024px) 13rem, (min-width: 640px) 33vw, 50vw' : '(min-width: 640px) 50vw, 100vw' }}" @endif
                                 @if ($img->width) width="{{ $img->width }}" height="{{ $img->height }}" @endif
                                 class="w-full object-cover object-top transition duration-500 group-hover:scale-[1.03] {{ $kind === 'screenshot' ? 'aspect-[9/19]' : ($kind === 'certificate' || $kind === 'hardware' ? 'aspect-[3/4]' : 'aspect-video') }}">
                            @if ($img->caption)
                                <span class="block px-3 py-2 text-xs text-muted">{{ $img->caption }}</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</div>
