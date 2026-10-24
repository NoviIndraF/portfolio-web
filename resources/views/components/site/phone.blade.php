{{--
    Static phone frame. It is also the mount point and no-WebGL fallback for the 3D PhoneViewer (stage 4):
    the data-* attributes give the 3D viewer its screens, accent color and scene.
--}}
@props([
    'project' => null,       // Project (for placeholder, accent, scene, model)
    'screens' => [],         // [['url' => ..., 'caption' => ...], ...]
    'width' => '15rem',
    'scene' => null,         // overrides project scene (e.g. "hero")
    'interactive' => true,   // swipeable screens + 3D mount
    'transitionName' => null, // view-transition-name for page transitions
])
@php
    $screens = collect($screens)->values();
    $accent = $project?->accent_color;
    $sceneType = $scene ?? $project?->scene_type;
@endphp
<div {{ $attributes->class('relative flex flex-col items-center') }}
     @if ($accent) style="--accent: {{ $accent }}" @endif
     @if ($interactive)
         data-phone-viewer
         data-scene="{{ $sceneType }}"
         data-accent="{{ $accent ?? '' }}"
         data-title="{{ $project?->title }}"
         data-screens="{{ $screens->pluck('url')->toJson() }}"
         data-model="{{ $project?->modelUrl() }}"
     @endif>
    <div class="phone" style="--phone-w: {{ $width }}{{ $transitionName ? '; view-transition-name: '.$transitionName : '' }}" data-phone-static>
        <div class="phone-notch" aria-hidden="true"></div>
        <div class="phone-screen">
            @if ($screens->isNotEmpty())
                <div class="no-scrollbar flex h-full {{ $interactive ? 'snap-x snap-mandatory overflow-x-auto overscroll-x-contain' : 'overflow-hidden' }}"
                     @if ($interactive) data-screen-rail tabindex="0" aria-label="{{ __('site.project.swipe_hint') }}" @endif>
                    @foreach ($interactive ? $screens : $screens->take(1) as $i => $s)
                        <img src="{{ $s['url'] }}" @if (! empty($s['srcset'])) srcset="{{ $s['srcset'] }}" sizes="{{ $width }}" @endif
                             alt="{{ $s['caption'] ?? '' }}"
                             class="h-full w-full shrink-0 snap-center object-cover object-top"
                             @if ($i > 0 || ! $interactive) loading="lazy" @else fetchpriority="high" @endif
                             decoding="async" draggable="false">
                    @endforeach
                </div>
            @elseif ($project)
                <x-site.phone-placeholder :project="$project" />
            @endif
        </div>
    </div>

    @if ($interactive && $screens->count() > 1)
        <div class="mt-4 flex items-center gap-1.5" data-screen-dots aria-hidden="true">
            @foreach ($screens as $i => $s)
                <span class="h-1.5 rounded-full transition-all {{ $i === 0 ? 'w-5 bg-accent' : 'w-1.5 bg-fg/25' }}"></span>
            @endforeach
        </div>
    @endif
</div>
