// Smooth scrolling (Lenis), scroll reveals and a light parallax (GSAP + ScrollTrigger).
// Skipped entirely for prefers-reduced-motion. Content is fully visible without it.
export async function initMotion({ reduced }) {
    if (reduced) return;

    const [{ gsap }, { ScrollTrigger }, { default: Lenis }] = await Promise.all([
        import('gsap'),
        import('gsap/ScrollTrigger'),
        import('lenis'),
    ]);
    gsap.registerPlugin(ScrollTrigger);

    // Lenis only on precise pointers (mouse/trackpad); touch keeps native scrolling.
    if (window.matchMedia('(pointer: fine)').matches) {
        const lenis = new Lenis({ autoRaf: false, anchors: { offset: -72 }, prevent: (node) => node.closest?.('dialog, [data-rail]') });
        lenis.on('scroll', ScrollTrigger.update);
        gsap.ticker.add((time) => lenis.raf(time * 1000));
        gsap.ticker.lagSmoothing(0);
        document.querySelectorAll('dialog').forEach((d) => {
            new MutationObserver(() => (d.open ? lenis.stop() : lenis.start())).observe(d, { attributes: true, attributeFilter: ['open'] });
        });
    }

    // Reveals: only for elements still below the fold, so nothing visible ever disappears.
    // IntersectionObserver decides when (robust to layout changes); GSAP does the animation.
    const below = (el) => el.getBoundingClientRect().top > window.innerHeight * 0.92;
    const targets = new Map();
    gsap.utils.toArray('[data-reveal]').filter(below).forEach((el) => targets.set(el, [el]));
    gsap.utils.toArray('[data-reveal-stagger]').filter(below).forEach((el) => targets.set(el, [...el.children]));
    targets.forEach((items) => gsap.set(items, { y: 24, opacity: 0 }));

    const io = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            io.unobserve(entry.target);
            gsap.to(targets.get(entry.target), { y: 0, opacity: 1, duration: 0.6, ease: 'power2.out', stagger: 0.07, clearProps: 'transform,opacity' });
        });
    }, { rootMargin: '0px 0px -8% 0px' });
    targets.forEach((_, el) => io.observe(el));

    // Subtle parallax on the hero / project header glow.
    gsap.utils.toArray('section.isolate > .-z-10:first-child').forEach((bg) => {
        gsap.to(bg, { yPercent: 18, ease: 'none', scrollTrigger: { trigger: bg.parentElement, start: 'top top', end: 'bottom top', scrub: true } });
    });
}
