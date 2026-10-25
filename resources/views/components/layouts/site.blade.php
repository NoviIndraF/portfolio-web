@props([
    'title' => null,
    'description' => null,
    'image' => null,
    'accent' => null,
])
@php
    $pageTitle = $title ? $title.' | '.$profile->name : $settings->site_title;
    $metaDescription = str($description ?: $settings->meta_description ?: $profile->tagline)->squish()->limit(160);
    $ogImage = $image ?: $site->defaultOgImage();
    $defaultTheme = array_key_exists($settings->default_theme, \App\Models\Setting::THEMES) ? $settings->default_theme : 'system';
    $sameAs = array_values(array_filter([$profile->github, $profile->linkedin]));
    // Built here: inside @php, Blade does not treat "@context" as a directive.
    $jsonLd = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => $profile->name,
        'jobTitle' => trim(str($profile->tagline)->before('|')) ?: null,
        'description' => $profile->tagline,
        'url' => url('/'),
        'image' => $profile->photoUrl(),
        'sameAs' => $sameAs ?: null,
    ]);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      data-3d="{{ $settings->toggle_3d ? 'on' : 'off' }}"
      data-default-theme="{{ $defaultTheme }}" data-theme="{{ $defaultTheme === 'light' ? 'light' : 'dark' }}">
<head>
    {{-- Before anything paints: the visitor's saved choice, else the admin default (or the device setting). --}}
    <script>
        (() => {
            let theme;
            try { theme = localStorage.getItem('theme'); } catch (e) {}
            if (theme !== 'light' && theme !== 'dark') {
                theme = @js($defaultTheme);
                if (theme === 'system') theme = matchMedia('(prefers-color-scheme: light)').matches ? 'light' : 'dark';
            }
            document.documentElement.dataset.theme = theme;
        })();
    </script>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $pageTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <meta name="theme-color" content="{{ $settings->background_color }}">

    {{-- Open Graph / link previews --}}
    <meta property="og:type" content="{{ $title ? 'article' : 'website' }}">
    <meta property="og:site_name" content="{{ $settings->site_title }}">
    <meta property="og:title" content="{{ $pageTitle }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:locale" content="{{ str_replace('-', '_', app()->getLocale()) }}">
    @if ($ogImage)
        <meta property="og:image" content="{{ $ogImage }}">
        <meta name="twitter:image" content="{{ $ogImage }}">
    @endif
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $pageTitle }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">

    <script type="application/ld+json">
        {!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
    </script>

    <style>
        :root {
            --c-bg: {{ $settings->background_color }};
            --c-text: {{ $settings->text_color }};
            --c-primary: {{ $settings->primary_color }};
        }
        :root[data-theme="light"] {
            --c-bg: {{ $settings->light_background_color }};
            --c-text: {{ $settings->light_text_color }};
        }
    </style>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-dvh font-sans antialiased" @if ($accent) style="--accent: {{ $accent }}" @endif>
    <a href="#main" class="sr-only z-50 rounded-full bg-primary px-4 py-2 font-semibold text-bg focus:not-sr-only focus:fixed focus:top-3 focus:left-3">
        {{ __('site.skip_to_content') }}
    </a>

    <x-site.header />

    <main id="main">
        {{ $slot }}
    </main>

    <footer class="border-t border-line py-8 text-sm text-muted">
        <div class="container-site flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <p>&copy; {{ date('Y') }} {{ $profile->name }}</p>
            <p>{{ __('site.footer') }}</p>
        </div>
    </footer>

    <x-site.chat-widget />
</body>
</html>
