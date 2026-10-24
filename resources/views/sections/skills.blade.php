<section id="skills" class="py-16 sm:py-24" aria-labelledby="skills-title">
    <div class="container-site">
        <x-site.section-heading id="skills-title" :eyebrow="__('site.skills.eyebrow')" :title="__('site.skills.title')" />

        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3" data-skills data-reveal-stagger>
            @foreach ($skillGroups as $group)
                <div class="card group relative overflow-hidden p-6 transition hover:border-primary/50" data-skill-group>
                    <div class="pointer-events-none absolute -top-16 -right-16 size-40 rounded-full bg-primary/10 blur-2xl transition group-hover:bg-primary/20" aria-hidden="true"></div>
                    <h3 class="text-lg font-bold">{{ $group->name }}</h3>
                    <ul class="mt-4 flex flex-wrap gap-2">
                        @foreach ($group->skills as $skill)
                            <li>
                                <x-site.tech-chip :name="$skill->name" :icon="$skill->icon"
                                    class="text-sm transition hover:-translate-y-0.5 hover:border-primary/50" />
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</section>
