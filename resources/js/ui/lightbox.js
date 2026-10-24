// Gallery lightbox: keyboard, swipe to change image, pinch / wheel / double-tap to zoom, drag to pan.
export function initLightbox() {
    const dialog = document.querySelector('[data-lightbox]');
    const items = [...document.querySelectorAll('[data-lightbox-item]')];
    if (!dialog || !items.length) return;

    const img = dialog.querySelector('[data-lightbox-img]');
    const stage = dialog.querySelector('[data-lightbox-stage]');
    const caption = dialog.querySelector('[data-lightbox-caption]');
    const counter = dialog.querySelector('[data-lightbox-counter]');

    let index = 0;
    let scale = 1;
    let x = 0;
    let y = 0;
    let lastFocus = null;

    const apply = (animate = false) => {
        img.style.transition = animate ? 'transform 200ms ease' : 'none';
        img.style.transform = `translate(${x}px, ${y}px) scale(${scale})`;
    };
    const resetZoom = () => {
        scale = 1;
        x = 0;
        y = 0;
        apply(true);
    };

    const show = (i) => {
        index = (i + items.length) % items.length;
        const item = items[index];
        img.src = item.dataset.src;
        img.alt = item.querySelector('img')?.alt ?? '';
        caption.textContent = item.dataset.caption ?? '';
        counter.textContent = `${index + 1} / ${items.length}`;
        resetZoom();
    };

    const open = (i) => {
        lastFocus = document.activeElement;
        show(i);
        dialog.showModal();
        document.body.style.overflow = 'hidden';
    };
    const close = () => dialog.close();

    dialog.addEventListener('close', () => {
        document.body.style.overflow = '';
        lastFocus?.focus();
    });

    items.forEach((item, i) => item.addEventListener('click', (e) => {
        e.preventDefault(); // links still work without JavaScript
        open(i);
    }));
    document.querySelectorAll('[data-lightbox-open]').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const kind = btn.dataset.lightboxOpen;
            const i = items.findIndex((it) => it.dataset.kind === kind);
            open(Math.max(0, i));
        });
    });

    dialog.querySelector('[data-lightbox-close]').addEventListener('click', close);
    dialog.querySelector('[data-lightbox-prev]').addEventListener('click', () => show(index - 1));
    dialog.querySelector('[data-lightbox-next]').addEventListener('click', () => show(index + 1));
    dialog.addEventListener('keydown', (e) => {
        if (e.key === 'ArrowRight') show(index + 1);
        if (e.key === 'ArrowLeft') show(index - 1);
    });
    // Click on the dark area closes.
    stage.addEventListener('click', (e) => {
        if (e.target === stage && scale === 1) close();
    });

    // --- Pointer gestures ---
    const pointers = new Map();
    let start = null; // gesture start state
    let lastTap = 0;

    const dist = () => {
        const [a, b] = [...pointers.values()];
        return Math.hypot(a.x - b.x, a.y - b.y);
    };

    img.addEventListener('pointerdown', (e) => {
        img.setPointerCapture(e.pointerId);
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
        start = { x, y, scale, px: e.clientX, py: e.clientY, d: pointers.size === 2 ? dist() : 0, t: Date.now() };

        // Double-tap / double-click toggles zoom.
        if (pointers.size === 1 && Date.now() - lastTap < 280) {
            scale > 1 ? resetZoom() : ((scale = 2.5), apply(true));
            lastTap = 0;
        } else {
            lastTap = Date.now();
        }
    });

    img.addEventListener('pointermove', (e) => {
        if (!pointers.has(e.pointerId) || !start) return;
        pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (pointers.size === 2) {
            // Pinch zoom
            scale = Math.min(5, Math.max(1, start.scale * (dist() / (start.d || 1))));
            apply();
        } else if (scale > 1) {
            // Pan when zoomed
            x = start.x + (e.clientX - start.px);
            y = start.y + (e.clientY - start.py);
            apply();
        } else {
            // Follow finger horizontally as swipe feedback
            x = e.clientX - start.px;
            apply();
        }
    });

    const end = (e) => {
        if (!pointers.has(e.pointerId)) return;
        pointers.delete(e.pointerId);
        if (pointers.size > 0) {
            // Continue with the remaining finger after a pinch.
            const [p] = pointers.values();
            start = { x, y, scale, px: p.x, py: p.y, d: 0, t: Date.now() };
            return;
        }
        if (scale <= 1.02) {
            const dx = e.clientX - (start?.px ?? e.clientX);
            if (Math.abs(dx) > 60) show(index + (dx < 0 ? 1 : -1));
            else resetZoom();
        }
        start = null;
    };
    img.addEventListener('pointerup', end);
    img.addEventListener('pointercancel', end);

    // Mouse wheel / trackpad zoom
    stage.addEventListener('wheel', (e) => {
        e.preventDefault();
        scale = Math.min(5, Math.max(1, scale * (e.deltaY < 0 ? 1.15 : 0.87)));
        if (scale === 1) {
            x = 0;
            y = 0;
        }
        apply(true);
    }, { passive: false });
}
