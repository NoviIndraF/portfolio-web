// Decides whether 3D runs, then lazy-loads Three.js only for viewers near the viewport.
// Falls back to the static phone when 3D is off in settings, WebGL is missing, or Save-Data is on.
export const prefersReducedMotion = () => window.matchMedia('(prefers-reduced-motion: reduce)').matches;
export const isMobile = () => window.matchMedia('(pointer: coarse)').matches || window.innerWidth < 768;

function hasWebGL() {
    try {
        const c = document.createElement('canvas');
        return !!(c.getContext('webgl2') || c.getContext('webgl'));
    } catch {
        return false;
    }
}

export function initThree() {
    const html = document.documentElement;
    const enabled = html.dataset['3d'] !== 'off';
    if (!enabled || navigator.connection?.saveData || !hasWebGL()) {
        html.classList.add('no-3d');
        return;
    }

    const opts = { reduced: prefersReducedMotion(), mobile: isMobile() };

    const start = () => {
        const io = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                io.unobserve(entry.target);
                const el = entry.target;
                if (el.matches('[data-phone-viewer]')) {
                    import('../three/phone-viewer').then(({ PhoneViewer3D }) => new PhoneViewer3D(el, opts)).catch(console.warn);
                } else if (el.matches('[data-scene="scroll-ornament"]')) {
                    // Scroll-driven, so it stays off for reduced motion.
                    if (opts.reduced) return;
                    const color = getComputedStyle(html).getPropertyValue('--c-primary').trim();
                    import('../scenes/scroll-ornament').then(({ default: mount }) => mount(el, { ...opts, color })).catch(console.warn);
                } else {
                    const color = getComputedStyle(html).getPropertyValue('--c-primary').trim();
                    import('../scenes/hero').then(({ default: mount }) => mount(el, { ...opts, color })).catch(console.warn);
                }
            });
        }, { rootMargin: '200px' });
        document.querySelectorAll('[data-phone-viewer], [data-scene="hero-particles"], [data-scene="scroll-ornament"]').forEach((el) => io.observe(el));
    };

    // Load 3D on the visitor's first interaction, so it never competes with the first paint.
    onFirstInteraction(start);
}

/** Runs `fn` once, on the first scroll / pointer / touch / key event (after the page has loaded). */
export function onFirstInteraction(fn) {
    const events = ['pointermove', 'pointerdown', 'touchstart', 'wheel', 'scroll', 'keydown'];
    let done = false;
    const run = () => {
        if (done) return;
        done = true;
        events.forEach((e) => window.removeEventListener(e, run));
        const go = () => ('requestIdleCallback' in window ? requestIdleCallback(fn, { timeout: 500 }) : setTimeout(fn, 50));
        if (document.readyState === 'complete') go();
        else window.addEventListener('load', go, { once: true });
    };
    events.forEach((e) => window.addEventListener(e, run, { passive: true }));
}
