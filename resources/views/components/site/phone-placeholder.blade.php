{{-- Phone screen for projects without public screenshots: app name + simple animated visuals (no data claims). --}}
@props(['project'])
<div class="flex h-full flex-col bg-[radial-gradient(120%_70%_at_50%_0%,color-mix(in_oklab,var(--accent,var(--c-primary))_35%,#05070d),#05070d_70%)] px-[9%] pt-[22%] pb-[12%] text-white"
     style="--accent: {{ $project->accent_color }}">
    <div class="flex items-center gap-2">
        <span class="grid size-7 place-items-center rounded-lg bg-accent text-[0.6rem] font-extrabold text-[#05070d]">
            {{ mb_substr($project->title, 0, 1) }}
        </span>
        <span class="text-[0.7rem] leading-tight font-bold">{{ $project->title }}</span>
    </div>

    @if ($project->scene_type === 'analytics')
        {{-- Neutral abstract bars --}}
        <div class="mt-[14%] flex h-[42%] items-end gap-[6%]">
            @foreach ([55, 80, 40, 68, 90, 50] as $i => $h)
                <span class="flex-1 rounded-t-md bg-accent/80 motion-safe:animate-pulse"
                      style="height: {{ $h }}%; animation-delay: {{ $i * 150 }}ms"></span>
            @endforeach
        </div>
    @else
        {{-- Sensor gauges --}}
        <div class="mt-[14%] grid grid-cols-2 gap-3">
            @foreach ([['Temp', 0.62], ['Water', 0.78], ['Humidity', 0.55], ['Light', 0.7]] as $i => [$label, $v])
                <div class="flex flex-col items-center rounded-xl bg-white/6 p-2">
                    <svg viewBox="0 0 36 36" class="size-12 -rotate-90" aria-hidden="true">
                        <circle cx="18" cy="18" r="15" fill="none" stroke="rgb(255 255 255 / .12)" stroke-width="3.5"/>
                        <circle cx="18" cy="18" r="15" fill="none" stroke="var(--accent,var(--c-primary))" stroke-width="3.5"
                                stroke-linecap="round" pathLength="100" stroke-dasharray="{{ $v * 100 }} 100"
                                class="motion-safe:animate-pulse" style="animation-delay: {{ $i * 200 }}ms"/>
                    </svg>
                    <span class="mt-1 text-[0.55rem] font-semibold text-white/75">{{ $label }}</span>
                </div>
            @endforeach
        </div>
    @endif

    <div class="mt-auto space-y-1.5" aria-hidden="true">
        <div class="h-1.5 w-2/3 rounded-full bg-white/15"></div>
        <div class="h-1.5 w-1/2 rounded-full bg-white/10"></div>
    </div>
</div>
