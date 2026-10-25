<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" />
        @if (! empty($skillProjects))
            <p class="mt-3 text-sm text-muted">{{ __('site.skills.hint') }}</p>
        @endif

        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-skills data-reveal-stagger>
            @foreach ($skillGroups as $group)
                <div class="card group relative overflow-hidden p-6 transition hover:border-primary/50" data-skill-group>
                    <div class="pointer-events-none absolute -top-16 -right-16 size-40 rounded-full bg-primary/10 blur-2xl transition group-hover:bg-primary/20" aria-hidden="true"></div>
                    <h3 class="text-lg font-bold">{{ $group->name }}</h3>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($group->skills as $skill)
                            <li>
                                @if (isset($skillProjects[$skill->id]))
                                    {{-- Linked to projects: resources/js/ui/interactions.js (initSkillLinks) --}}
                                    <button type="button" class="relative rounded-full transition hover:-translate-y-0.5 aria-pressed:[&>span]:border-primary aria-pressed:[&>span]:bg-primary/15"
                                            aria-pressed="false" data-skill="{{ $skill->name }}" data-skill-projects="{{ json_encode($skillProjects[$skill->id]) }}">
                                        <x-site.tech-chip :name="$skill->name" :icon="$skill->icon" class="text-sm hover:border-primary/50" />
                                    </button>
                                @else
                                    <x-site.tech-chip :name="$skill->name" :icon="$skill->icon" class="text-sm" />
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>

        <div class="mt-6 hidden flex-wrap items-center gap-2 rounded-2xl border border-primary/40 bg-primary/10 p-4 text-sm" data-skill-result aria-live="polite">
            <span class="font-semibold" data-skill-result-label></span>
            <span class="contents" data-skill-result-links></span>
            <button type="button" class="ml-auto text-muted underline-offset-4 hover:text-fg hover:underline" data-skill-clear>{{ __('site.skills.clear') }}</button>
        </div>
        <template data-skill-used-in>{{ __('site.skills.used_in') }}</template>
    </div>
</section>
