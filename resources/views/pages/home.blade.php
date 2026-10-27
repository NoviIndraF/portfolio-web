<x-layouts.site>
    {{-- 3D companion behind the content that morphs per section (resources/js/scenes/scroll-ornament.js) --}}
    <div class="pointer-events-none fixed inset-0 -z-10" data-scene="scroll-ornament" aria-hidden="true"
         data-sections="{{ $sections->mapWithKeys(fn ($s) => [$s->anchor => $s->type])->toJson() }}"></div>

    @include('sections.hero')

    {{-- Sections are managed in Admin → Home sections (order, visibility, texts). --}}
    @foreach ($sections as $section)
        @includeIf('sections.'.$section->type, ['section' => $section])
    @endforeach
</x-layouts.site>
