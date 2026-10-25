// Static phone fallback: swipe between screenshots and keep the dots in sync.
export function initPhoneScreens() {
    document.querySelectorAll('[data-phone-viewer]').forEach((viewer) => {
        const rail = viewer.querySelector('[data-screen-rail]');
        const dots = viewer.querySelectorAll('[data-screen-dots] > span');
        if (!rail) return;

        let current = 0;
        const update = () => {
            const i = Math.round(rail.scrollLeft / rail.clientWidth);
            if (i !== current) {
                current = i;
                viewer.dispatchEvent(new CustomEvent('phone:screen', { detail: { index: i } }));
            }
            dots.forEach((d, n) => {
                d.classList.toggle('w-5', n === i);
                d.classList.toggle('bg-accent', n === i);
                d.classList.toggle('w-1.5', n !== i);
                d.classList.toggle('bg-fg/25', n !== i);
            });
        };
        rail.addEventListener('scroll', () => requestAnimationFrame(update), { passive: true });

        // Same "goto" API as the 3D viewer (used by feature cards); ignored while 3D is shown.
        viewer.addEventListener('phone:goto', (e) => {
            if (!viewer.classList.contains('is-3d')) rail.scrollTo({ left: e.detail.index * rail.clientWidth }); // instant: smooth + snap can stall
        });

        rail.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') {
                e.preventDefault();
                rail.scrollBy({ left: (e.key === 'ArrowRight' ? 1 : -1) * rail.clientWidth, behavior: 'smooth' });
            }
        });
    });
}
