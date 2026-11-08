{{-- Robot mascot of the AI chat. Add class "robot-live" to animate it (float, blink, chat states): .robot-* in app.css --}}
<svg viewBox="0 0 32 32" aria-hidden="true" {{ $attributes->merge(['class' => 'robot']) }}>
    <g class="robot-body">
        <path class="robot-stem" d="M16 7V4" />
        <circle class="robot-antenna" cx="16" cy="3.3" r="2.3" />
        <rect class="robot-face" x="2.5" y="13.5" width="4" height="7" rx="2" />
        <rect class="robot-face" x="25.5" y="13.5" width="4" height="7" rx="2" />
        <rect class="robot-face" x="5" y="6.5" width="22" height="20" rx="9" />
        <g class="robot-eyes">
            <g class="robot-eye">
                <ellipse class="robot-ink" cx="12" cy="15.5" rx="2.4" ry="2.9" />
                <circle class="robot-shine" cx="12.9" cy="14.4" r="0.9" />
            </g>
            <g class="robot-eye">
                <ellipse class="robot-ink" cx="20" cy="15.5" rx="2.4" ry="2.9" />
                <circle class="robot-shine" cx="20.9" cy="14.4" r="0.9" />
            </g>
        </g>
        <ellipse class="robot-blush" cx="9.3" cy="20.2" rx="1.8" ry="1.1" />
        <ellipse class="robot-blush" cx="22.7" cy="20.2" rx="1.8" ry="1.1" />
        <path class="robot-mouth" d="M13.6 19.6h4.8a2.4 2.4 0 0 1-4.8 0z" />
    </g>
</svg>
