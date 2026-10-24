<x-layouts.site>
    @include('sections.hero')

    {{-- Sections are managed in Admin → Home sections (order, visibility, texts). --}}
    @foreach ($sections as $section)
        @includeIf('sections.'.$section->type, ['section' => $section])
    @endforeach
</x-layouts.site>
