<section id="projects" class="relative py-16 sm:py-24" aria-labelledby="projects-title">
    <div class="container-site flex flex-wrap items-end justify-between gap-6">
        <x-site.section-heading id="projects-title" :eyebrow="__('site.projects.eyebrow')" :title="__('site.projects.title')" :intro="__('site.projects.intro')" />
        <div class="hidden gap-2 sm:flex">
            <button type="button" class="btn-ghost size-11 px-0" data-rail-prev="projects-rail" aria-label="{{ __('site.projects.prev') }}">
                <x-heroicon-o-arrow-left class="size-5" aria-hidden="true" />
            </button>
            <button type="button" class="btn-ghost size-11 px-0" data-rail-next="projects-rail" aria-label="{{ __('site.projects.next') }}">
                <x-heroicon-o-arrow-right class="size-5" aria-hidden="true" />
            </button>
        </div>
    </div>

    {{-- Drag/swipe rail; stage 4 adds the 3D carousel effect on top of this. --}}
    <div id="projects-rail" class="no-scrollbar mt-10 flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-px-4 px-4 pb-6 sm:scroll-px-6 sm:px-6 lg:px-[max(2rem,calc((100vw-72rem)/2+2rem))] lg:scroll-px-[max(2rem,calc((100vw-72rem)/2+2rem))]"
         data-rail data-project-carousel data-reveal>
        @foreach ($projects as $project)
            <x-site.project-card :project="$project" />
        @endforeach
    </div>
</section>
