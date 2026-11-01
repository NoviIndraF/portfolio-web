<section id="{{ $section->anchor }}" class="py-16 sm:py-24" aria-labelledby="{{ $section->anchor }}-title">
    <div class="container-site">
        <x-site.section-heading id="{{ $section->anchor }}-title" :eyebrow="$section->text('eyebrow')" :title="$section->text('title')" :intro="$section->text('intro')" />
        @if (! empty($skillProjects))
            <p class="mt-3 text-sm text-muted">{{ __('site.skills.hint') }}</p>
        @endif

        {{-- v2: one row per category, tools as an icon grid (like the reference's tool grid). --}}
        <div class="mt-10 divide-y divide-line border-y border-line" data-skills data-reveal-stagger>
            @foreach ($skillGroups as $group)
                <div class="grid gap-4 py-5 md:grid-cols-[16rem_1fr] md:items-center md:gap-10 lg:grid-cols-[20rem_1fr]">
                    <div>
                        <p class="eyebrow">{{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }} &middot; {{ trans_choice('site.skills.count', $group->skills->count()) }}</p>
                        <h3 class="item-title mt-2">{{ $group->name }}</h3>
                    </div>
                    <ul class="grid grid-cols-[repeat(auto-fill,minmax(6.5rem,1fr))] gap-2">
                        @foreach ($group->skills as $skill)
                            @php
                                $svg = \App\Support\Icon::svg(\App\Support\Icon::slugFor($skill->name, $skill->icon), 'size-6 sm:size-7');
                                $linked = isset($skillProjects[$skill->id]);
                            @endphp
                            <li class="h-full">
                                {{-- Linked to projects: resources/js/ui/interactions.js (initSkillLinks) --}}
                                <{{ $linked ? 'button' : 'div' }} @class(['skill-tile', 'is-linked' => $linked])
                                    @if ($linked) type="button" aria-pressed="false" data-skill="{{ $skill->name }}" data-skill-projects="{{ json_encode($skillProjects[$skill->id]) }}" @endif>
                                    @if ($svg)
                                        {{ $svg }}
                                    @else
                                        <span class="font-display text-2xl leading-none sm:text-3xl" aria-hidden="true">{{ mb_strtoupper(mb_substr($skill->name, 0, 2)) }}</span>
                                    @endif
                                    <span class="skill-name">{{ $skill->name }}</span>
                                </{{ $linked ? 'button' : 'div' }}>
                            </li>
                        @endforeach
                    </ul>
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
