// All projects page: filter cards by type with the chip buttons.
export function initFilter() {
    const bar = document.querySelector('[data-filter]');
    const grid = document.querySelector('[data-filter-grid]');
    if (!bar || !grid) return;

    bar.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-filter-value]');
        if (!btn) return;
        const value = btn.dataset.filterValue;
        bar.querySelectorAll('[data-filter-value]').forEach((b) => b.setAttribute('aria-pressed', String(b === btn)));
        grid.querySelectorAll('[data-type]').forEach((card) => {
            card.hidden = value !== '' && card.dataset.type !== value;
        });
    });
}
