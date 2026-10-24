// Horizontal scroll-snap rails: prev/next buttons and mouse drag (touch uses native swipe).
export function initRails() {
    document.querySelectorAll('[data-rail]').forEach((rail) => {
        const step = () => (rail.querySelector(':scope > *')?.getBoundingClientRect().width ?? 300) + 20;

        document.querySelector(`[data-rail-prev="${rail.id}"]`)?.addEventListener('click', () => rail.scrollBy({ left: -step(), behavior: 'smooth' }));
        document.querySelector(`[data-rail-next="${rail.id}"]`)?.addEventListener('click', () => rail.scrollBy({ left: step(), behavior: 'smooth' }));

        enableMouseDrag(rail);
    });
}

function enableMouseDrag(rail) {
    let startX = 0;
    let startScroll = 0;
    let dragging = false;
    let moved = false;

    rail.addEventListener('pointerdown', (e) => {
        if (e.pointerType !== 'mouse' || e.button !== 0) return;
        dragging = true;
        moved = false;
        startX = e.clientX;
        startScroll = rail.scrollLeft;
    });

    rail.addEventListener('pointermove', (e) => {
        if (!dragging) return;
        const dx = e.clientX - startX;
        if (!moved && Math.abs(dx) > 5) {
            moved = true;
            rail.setPointerCapture(e.pointerId);
            rail.style.scrollSnapType = 'none'; // free scroll while dragging
            rail.style.cursor = 'grabbing';
        }
        if (moved) rail.scrollLeft = startScroll - dx;
    });

    const end = () => {
        if (!dragging) return;
        dragging = false;
        rail.style.cursor = '';
        if (moved) {
            // Re-enable snapping so the rail settles on a card.
            const left = rail.scrollLeft;
            rail.style.scrollSnapType = '';
            rail.scrollLeft = left;
        }
    };
    rail.addEventListener('pointerup', end);
    rail.addEventListener('pointercancel', end);

    // Don't follow a card link after a drag.
    rail.addEventListener('click', (e) => {
        if (moved) {
            e.preventDefault();
            e.stopPropagation();
            moved = false;
        }
    }, true);
}
