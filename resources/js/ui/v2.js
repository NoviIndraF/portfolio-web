// v2 redesign: intro counter (0.00 → 1.00) and the live clock in the header.
// Both are optional: the page works and reads the same without them.

export function initIntro() {
    const intro = document.querySelector('[data-intro]');
    const count = intro?.querySelector('[data-intro-count]');
    if (!count || !document.documentElement.classList.contains('intro-on')) return;

    const duration = 1400;
    const start = performance.now();
    const tick = (now) => {
        const t = Math.min((now - start) / duration, 1);
        count.textContent = (1 - (1 - t) ** 3).toFixed(2); // ease-out
        if (t < 1) return requestAnimationFrame(tick);
        intro.classList.add('is-done');
        // Desktop only: phones keep loading 3D on the first touch, to save data and CPU.
        if (matchMedia('(pointer: fine) and (min-width: 1024px)').matches) window.dispatchEvent(new Event('intro:done'));
    };
    requestAnimationFrame(tick);
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
