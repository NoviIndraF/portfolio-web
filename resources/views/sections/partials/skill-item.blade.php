{{-- One skill (sections/skills): an icon tile or a text chip. Linked to projects: resources/js/ui/interactions.js (initSkillLinks). --}}
@php($linked = isset($skillProjects[$skill->id]))
<{{ $linked ? 'button' : 'div' }} @class([$class, 'is-linked' => $linked])
    @if ($linked) type="button" aria-pressed="false" data-skill="{{ $skill->name }}" data-skill-projects="{{ json_encode($skillProjects[$skill->id]) }}" @endif>
    @if ($svg)
        {{ $svg }}
        <span class="skill-name">{{ $skill->name }}</span>
    @else
        {{ $skill->name }}
    @endif
</{{ $linked ? 'button' : 'div' }}>
