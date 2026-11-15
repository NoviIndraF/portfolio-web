{{-- One-page résumé for printing or "Save as PDF" (ResumeController). Stands alone: white page, no 3D, no chatbot, no site JS.
     Sections follow Admin → Home sections (visibility, menu label as heading). --}}
@php
    $role = trim(str($profile->tagline)->before('|'));
    $short = fn (?string $url) => (string) str($url)->after('://')->rtrim('/');
    $contacts = array_filter([
        $profile->email ? ['mailto:'.$profile->email, $profile->email] : null,
        $profile->linkedin ? [$profile->linkedin, $short($profile->linkedin)] : null,
        $profile->github ? [$profile->github, $short($profile->github)] : null,
        [route('home'), $short(route('home'))],
    ]);
    $heading = fn ($section) => $section->nav_label ?: $section->title;
    $h2 = 'border-b border-line pb-1 font-mono text-xs font-medium tracking-[0.16em] uppercase';
    $timelines = $sections->where('type', 'timeline');
    // Same split as the home page (sections/timeline): a timeline whose items have no description is a short list.
    [$compact, $detailed] = $timelines->partition(fn ($t) => $t->visibleItems()->every(fn ($i) => blank($i['description'] ?? null)));
    $skills = $sections->firstWhere('type', 'skills');
    $projectsSection = $sections->firstWhere('type', 'projects');
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('site.resume.title') }} | {{ $profile->name }}</title>
    <meta name="description" content="{{ str($profile->tagline)->squish()->limit(160, preserveWords: true) }}">
    <link rel="canonical" href="{{ url()->current() }}">
    <style>
        /* Always dark text on white, whatever theme the site uses. */
        :root {
            --c-bg: #ffffff;
            --c-text: #111111;
            --c-primary: {{ $settings->primary_color }};
        }
        @page {
            size: A4;
            margin: 10mm 14mm;
        }
    </style>
    @fonts
    @vite(['resources/css/app.css'])
</head>
<body class="bg-surface-2 print:bg-white">
    <div class="mx-auto flex max-w-[210mm] items-center justify-between gap-3 px-4 py-4 print:hidden">
        <a href="{{ route('home') }}" class="text-sm font-medium underline underline-offset-4">&larr; {{ __('site.resume.back') }}</a>
        <button type="button" onclick="window.print()" class="inline-flex min-h-11 items-center gap-2 rounded-sm bg-fg px-5 text-sm font-medium text-bg">
            <x-heroicon-o-printer class="size-4" aria-hidden="true" />
            {{ __('site.resume.print') }}
        </button>
    </div>

    <main class="mx-auto mb-10 max-w-[210mm] bg-bg px-5 py-8 text-xs leading-[1.3] shadow-lg sm:px-[14mm] sm:py-[10mm] print:m-0 print:max-w-none print:p-0 print:shadow-none">
        <header class="border-b-2 border-fg pb-3">
            <h1 class="display text-4xl">{{ $profile->name }}</h1>
            @if ($role)
                <p class="mt-2 font-mono text-xs font-medium tracking-[0.16em] uppercase">{{ $role }}</p>
            @endif
            <ul class="mt-2 flex flex-wrap gap-x-5 gap-y-0.5">
                @foreach ($contacts as [$href, $label])
                    <li><a href="{{ $href }}" class="underline decoration-line underline-offset-2">{{ $label }}</a></li>
                @endforeach
            </ul>
        </header>

        @if ($intro = $profile->bioIntro())
            <p class="mt-3 text-pretty">{{ $intro }}</p>
        @endif

        @if ($profile->stats)
            <dl class="mt-3 flex flex-wrap gap-x-8 gap-y-1">
                @foreach ($profile->stats as $stat)
                    <div class="flex items-baseline gap-1.5">
                        <dd class="order-first text-lg font-black tabular-nums">{{ $stat['value'] }}</dd>
                        <dt class="text-muted">{{ $stat['label'] }}</dt>
                    </div>
                @endforeach
            </dl>
        @endif

        {{-- Résumé order: work history (timelines with descriptions) first, then skills beside the short timelines
             (education, certificates), then projects. Which sections show still follows Admin → Home sections. --}}
        @foreach ($detailed as $section)
            <section class="mt-3">
                <h2 class="{{ $h2 }}">{{ $heading($section) }}</h2>
                @foreach ($section->visibleItems() as $item)
                    @include('pages.partials.resume-item', ['item' => $item, 'under' => $heading($section)])
                @endforeach
            </section>
        @endforeach

        <div class="grid gap-x-8 sm:grid-cols-2 print:grid-cols-2">
            @if ($skills && $skillGroups->isNotEmpty())
                <section class="mt-3 break-inside-avoid">
                    <h2 class="{{ $h2 }}">{{ $heading($skills) }}</h2>
                    <div class="mt-2 space-y-1">
                        @foreach ($skillGroups as $group)
                            <p><span class="font-bold">{{ $group->name }}:</span> {{ $group->skills->pluck('name')->join(', ') }}</p>
                        @endforeach
                    </div>
                </section>
            @endif
            @foreach ($compact as $section)
                <section class="mt-3 break-inside-avoid">
                    <h2 class="{{ $h2 }}">{{ $heading($section) }}</h2>
                    @foreach ($section->visibleItems() as $item)
                        @include('pages.partials.resume-item', ['item' => $item, 'under' => $heading($section)])
                    @endforeach
                </section>
            @endforeach
        </div>

        @if ($projectsSection && $projects->isNotEmpty())
            <section class="mt-3">
                <h2 class="{{ $h2 }}">{{ $heading($projectsSection) }}</h2>
                <ul class="mt-2 grid gap-x-8 gap-y-1.5 sm:grid-cols-2 print:grid-cols-2">
                    @foreach ($projects as $project)
                        <li class="break-inside-avoid">
                            <a href="{{ route('projects.show', $project) }}" class="font-bold underline decoration-line underline-offset-2">{{ $project->title }}</a>
                            <span class="text-muted">· {{ $project->typeLabel() }}@if ($project->year) · {{ $project->year }}@endif</span>
                            <span class="block">{{ $project->impact_line ?: $project->short_description }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    </main>
</body>
</html>
