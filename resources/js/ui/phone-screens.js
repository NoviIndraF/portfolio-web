// Static phone fallback: swipe between screenshots and keep the dots in sync.
export function initPhoneScreens() {
    document.querySelectorAll('[data-phone-viewer]').forEach((viewer) => {
        const rail = viewer.querySelector('[data-screen-rail]');
        const dots = viewer.querySelectorAll('[data-screen-dots] > span');
        if (!rail) return;

        const update = () => {
            const i = Math.round(rail.scrollLeft / rail.clientWidth);
            dots.forEach((d, n) => {
                d.classList.toggle('w-5', n === i);
                d.classList.toggle('bg-accent', n === i);
                d.classList.toggle('w-1.5', n !== i);
                d.classList.toggle('bg-fg/25', n !== i);
            });
        };
        rail.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });

        rail.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                rail.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * rail.clientWidth, behavior: 'smooth' });
            }
        });
    });
}
