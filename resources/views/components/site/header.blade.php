@php
    $site = app(\App\Support\Site::class);
    $profile = $site->profile();
    $home = request()->routeIs('home') ? '' : route('home');
    // Menu = visible home sections with a menu label (Admin → Home sections).
    $links = $site->nav()->mapWithKeys(fn ($s) => [$s->anchor => $s->nav_label]);
@endphp
<header class="sticky top-0 z-40 border-b border-line/60 bg-bg/80 backdrop-blur-md" data-header>
    <nav class="container-site flex h-16 items-center justify-between gap-4" aria-label="Main">
        <a href="{{ route('home') }}" class="flex items-center gap-2 font-bold tracking-tight">
            <span class="grid size-8 place-items-center rounded-xl bg-primary text-sm font-extrabold text-bg" aria-hidden="true">
                {{ str($profile->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}
            </span>
            <span class="sr-only sm:not-sr-only">{{ $profile->name }}</span>
        </a>

        <ul class="hidden items-center gap-1 md:flex" data-scrollspy>
            @foreach ($links as $id => $label)
                <li><a href="{{ $home }}#{{ $id }}" class="rounded-full px-3 py-2 text-sm font-medium text-muted transition hover:text-fg aria-[current=true]:bg-surface-2 aria-[current=true]:text-fg">{{ $label }}</a></li>
            @endforeach
        </ul>

        <div class="flex items-center gap-2">
            <button type="button" class="btn-ghost size-10 min-h-10 px-0" data-theme-toggle aria-pressed="true" aria-label="{{ __('site.theme.toggle') }}" title="{{ __('site.theme.toggle') }}">
                <x-heroicon-o-sun class="size-5 light:hidden" aria-hidden="true" />
                <x-heroicon-o-moon class="hidden size-5 light:block" aria-hidden="true" />
            </button>
            @if ($cv = $profile->cvUrl())
                <a href="{{ $cv }}" download class="btn-primary hidden min-h-9 px-4 sm:inline-flex">
                    <x-heroicon-o-arrow-down-tray class="size-4" aria-hidden="true" />
                    {{ __('site.cta.download_cv') }}
                </a>
            @endif
            <button type="button" class="btn-ghost min-h-10 px-3 md:hidden" data-menu-toggle aria-expanded="false" aria-controls="mobile-menu">
                <x-heroicon-o-bars-3 class="size-5" aria-hidden="true" />
                <span class="sr-only">{{ __('site.menu') }}</span>
            </button>
        </div>
    </nav>
    <div class="absolute inset-x-0 bottom-0 h-0.5 origin-left scale-x-0 bg-primary" data-scroll-progress aria-hidden="true"></div>

    <div id="mobile-menu" class="hidden border-t border-line md:hidden" data-menu>
        <ul class="container-site flex flex-col py-3">
            @foreach ($links as $id => $label)
                <li><a href="{{ $home }}#{{ $id }}" class="block rounded-xl px-3 py-3 text-base font-medium hover:bg-surface">{{ $label }}</a></li>
            @endforeach
            @if ($cv)
                <li class="pt-2"><a href="{{ $cv }}" download class="btn-primary w-full">{{ __('site.cta.download_cv') }}</a></li>
            @endif
        </ul>
    </div>
</header>
