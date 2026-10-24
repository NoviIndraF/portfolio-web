// Featured projects: coverflow-style 3D carousel on the swipe rail, pointer tilt on cards,
// and a smooth page transition (View Transitions) into the project detail.
export function initCarousel3d({ reduced }) {
    const rail = document.querySelector('[data-project-carousel]');
    if (!rail) return;
    const cards = [...rail.querySelectorAll('[data-project-card]')];

    // Name the clicked card's phone and title so the browser morphs them into the detail page.
    cards.forEach((card) => {
        card.querySelector('[data-project-link]')?.addEventListener('click', () => {
            card.querySelector('.phone')?.style.setProperty('view-transition-name', 'project-phone');
            card.querySelector('[data-project-link]')?.style.setProperty('view-transition-name', 'project-title');
        });
    });
    window.addEventListener('pageshow', () => {
        rail.querySelectorAll('[style*="view-transition-name"]').forEach((el) => el.style.removeProperty('view-transition-name'));
    });

    if (reduced) return;

    // Coverflow: cards rotate and shrink slightly with distance from the rail's center.
    let queued = false;
    const update = () => {
        queued = false;
        const r = rail.getBoundingClientRect();
        const center = r.left + r.width / 2;
        cards.forEach((card) => {
            const c = card.getBoundingClientRect();
            const o = Math.max(-2, Math.min(2, (c.left + c.width / 2 - center) / c.width));
            const a = Math.abs(o);
            card.style.transform = `perspective(1200px) rotateY(${-o * 12}deg) scale(${1 - Math.min(a, 1.5) * 0.06})`;
        });
    };
    const queue = () => {
        if (!queued) {
            queued = true;
            requestAnimationFrame(update);
        }
    };
    rail.addEventListener('scroll', queue, { passive: true });
    window.addEventListener('resize', queue);
    queue();

    // Tilt the inner card toward the mouse (desktop), with a soft glare.
    cards.forEach((card) => {
        const inner = card.querySelector('.card');
        card.addEventListener('pointermove', (e) => {
            if (e.pointerType !== 'mouse') return;
            const r = card.getBoundingClientRect();
            const x = (e.clientX - r.left) / r.width - 0.5;
            const y = (e.clientY - r.top) / r.height - 0.5;
            inner.style.transform = `rotateX(${-y * 8}deg) rotateY(${x * 10}deg) translateZ(0)`;
            inner.style.setProperty('--gx', `${(x + 0.5) * 100}%`);
            inner.style.setProperty('--gy', `${(y + 0.5) * 100}%`);
        });
        card.addEventListener('pointerleave', () => {
            inner.style.transform = '';
        });
    });
}

/** Skills: a glow that follows the pointer inside each group card. */
export function initSpotlight() {
    document.querySelectorAll('[data-skill-group]').forEach((el) => {
        el.addEventListener('pointermove', (e) => {
            const r = el.getBoundingClientRect();
            el.style.setProperty('--mx', `${e.clientX - r.left}px`);
            el.style.setProperty('--my', `${e.clientY - r.top}px`);
        });
    });
}
