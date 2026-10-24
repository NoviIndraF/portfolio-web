<x-layouts.site :title="__('site.all_projects.title')" :description="__('site.all_projects.intro')">
    <section class="container-site py-12 sm:py-16" aria-labelledby="all-projects-title">
        <a href="{{ route('home') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-muted hover:text-fg">
            <x-heroicon-o-arrow-left class="size-4" aria-hidden="true" /> {{ __('site.all_projects.back') }}
        </a>
        <h1 id="all-projects-title" class="mt-6 text-4xl font-extrabold tracking-tight sm:text-5xl">{{ __('site.all_projects.title') }}</h1>
        <p class="mt-3 max-w-2xl text-muted">{{ __('site.all_projects.intro') }}</p>

        @if ($types->count() > 1)
            <div class="mt-8 flex flex-wrap gap-2" role="group" aria-label="{{ __('site.all_projects.filter_label') }}" data-filter>
                <button type="button" class="chip px-4 py-2 text-sm aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-bg" aria-pressed="true" data-filter-value="">
                    {{ __('site.all_projects.filter_all') }} ({{ $projects->count() }})
                </button>
                @foreach ($types as $type => $label)
                    <button type="button" class="chip px-4 py-2 text-sm aria-pressed:border-primary aria-pressed:bg-primary aria-pressed:text-bg" aria-pressed="false" data-filter-value="{{ $type }}">
                        {{ $label }} ({{ $projects->where('type', $type)->count() }})
                    </button>
                @endforeach
            </div>
        @endif

        @if ($projects->isEmpty())
            <p class="mt-10 text-muted">{{ __('site.all_projects.empty') }}</p>
        @else
            <div class="mt-10 grid justify-items-center gap-6 sm:grid-cols-2 lg:grid-cols-3" data-filter-grid>
                @foreach ($projects as $project)
                    <x-site.project-card :project="$project" class="w-full! max-w-none! sm:w-full!" data-type="{{ $project->type }}" />
                @endforeach
            </div>
        @endif
    </section>
</x-layouts.site>
