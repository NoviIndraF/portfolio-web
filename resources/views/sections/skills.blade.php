{{-- v3: a light band with one card per category; tools with a logo as icon tiles, concepts (no logo) as text chips. --}}
<section id="{{ $section->anchor }}" class="band-inverse py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" size="md" center />
        @if (! empty($skillProjects))
            <p class="mt-3 text-center text-sm text-muted">{{ __('site.skills.hint') }}</p>
        @endif

        <div class="mt-10 grid gap-4 md:grid-cols-2 xl:grid-cols-3" data-skills data-reveal-stagger>
            @foreach ($skillGroups as $group)
                @php
                    $skills = $group->skills->map(fn ($skill) => [
                        'skill' => $skill,
                        'svg' => \App\Support\Icon::svg(\App\Support\Icon::slugFor($skill->name, $skill->icon), 'size-6 sm:size-7'),
                    ]);
                    [$tools, $concepts] = $skills->partition(fn ($s) => filled($s['svg']));
                @endphp
                <div class="card flex min-w-0 flex-col gap-5 p-5 sm:p-6">
                    <div>
                        <p class="eyebrow">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} &middot; {{ trans_choice('site.skills.count', $group->skills->count()) }}</p>
                        <h3 class="mt-2 font-item text-2xl leading-tight font-black">{{ $group->name }}</h3>
                    </div>
                    @if ($tools->isNotEmpty())
                        {{-- Phones: one swipeable row per category (keeps the page short); larger screens: a wrapping grid. --}}
                        <ul class="no-scrollbar -mx-5 flex snap-x snap-mandatory scroll-px-5 gap-2 overflow-x-auto px-5 pb-1 sm:mx-0 sm:grid sm:grid-cols-[repeat(auto-fill,minmax(5.5rem,1fr))] sm:overflow-visible sm:px-0 sm:pb-0">
                            @foreach ($tools as ['skill' => $skill, 'svg' => $svg])
                                <li class="h-full w-[5.5rem] shrink-0 snap-start sm:w-auto">
                                    @include('sections.partials.skill-item', ['skill' => $skill, 'class' => 'skill-tile', 'svg' => $svg])
                                </li>
                            @endforeach
                        </ul>
                    @endif
                    @if ($concepts->isNotEmpty())
                        <ul class="flex flex-wrap gap-2">
                            @foreach ($concepts as ['skill' => $skill])
                                <li>@include('sections.partials.skill-item', ['skill' => $skill, 'class' => 'skill-chip', 'svg' => null])</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="mt-6 hidden flex-wrap items-center gap-2 rounded-md border border-primary/40 bg-primary/10 p-4 text-sm" data-skill-result aria-live="polite">
            <span class="font-semibold" data-skill-result-label></span>
            <span class="contents" data-skill-result-links></span>
            <button type="button" class="ml-auto text-muted underline-offset-4 hover:text-fg hover:underline" data-skill-clear>{{ __('site.skills.clear') }}</button>
        </div>
        <template data-skill-used-in>{{ __('site.skills.used_in') }}</template>
    </div>
</section>
