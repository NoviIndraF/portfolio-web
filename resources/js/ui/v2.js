// v2 redesign: intro counter (0.00 → 1.00), pause control for moving strips and the live clock in the header.
// Both are optional: the page works and reads the same without them.

export function initIntro() {
    const intro = document.querySelector('[data-intro]');
    const count = intro?.querySelector('[data-intro-count]');
    if (!count || !document.documentElement.classList.contains('intro-on')) return;

    let done = false;
    const finish = () => {
        if (done) return;
        done = true;
        count.textContent = '1.00';
        intro.classList.add('is-done');
        removeEventListener('keydown', onKey);
        // Desktop only: phones keep loading 3D on the first touch, to save data and CPU.
        if (matchMedia('(pointer: fine) and (min-width: 1024px)').matches) window.dispatchEvent(new Event('intro:done'));
    };
    const onKey = (e) => e.key === 'Escape' && finish();
    // Skippable: click / tap anywhere on it, or Esc.
    intro.addEventListener('click', finish, { once: true });
    addEventListener('keydown', onKey);

    const duration = 1400;
    const start = performance.now();
    const tick = (now) => {
        if (done) return;
        const t = Math.min((now - start) / duration, 1);
        count.textContent = (1 - (1 - t) ** 3).toFixed(2); // ease-out
        if (t < 1) return requestAnimationFrame(tick);
        finish();
    };
    requestAnimationFrame(tick);
}

/** One pause/play choice for every moving strip (WCAG 2.2.2), remembered across visits. */
export function initMarqueeToggle() {
    const buttons = [...document.querySelectorAll('[data-marquee-toggle]')];
    if (!buttons.length) return;
    const html = document.documentElement;
    const render = () => {
        const paused = html.classList.contains('marquee-paused');
        buttons.forEach((b) => {
            const label = paused ? b.dataset.labelPlay : b.dataset.labelPause;
            b.setAttribute('aria-pressed', String(paused));
            b.setAttribute('aria-label', label);
            b.title = label;
        });
    };
    buttons.forEach((b) => b.addEventListener('click', () => {
        const paused = html.classList.toggle('marquee-paused');
        try {
            localStorage.setItem('marquee', paused ? 'paused' : 'playing');
        } catch {}
        render();
    }));
    render();
}

export function initClock() {
    const clock = document.querySelector('[data-clock]');
    const time = clock?.querySelector('[data-clock-time]');
    if (!time) return;

    let format;
    try {
        format = new Intl.DateTimeFormat(undefined, { hour: '2-digit', minute: '2-digit', timeZone: clock.dataset.tz, timeZoneName: 'short' });
    } catch {
        return clock.remove(); // unknown time zone
    }
    const update = () => (time.textContent = format.format(new Date()));
    update();
    setInterval(update, 30_000);
}
