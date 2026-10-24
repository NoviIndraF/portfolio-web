// Mobile menu toggle. Closes on link click and Escape.
export function initNav() {
    const toggle = document.querySelector('[data-menu-toggle]');
    const menu = document.querySelector('[data-menu]');
    if (!toggle || !menu) return;

    const setOpen = (open) => {
        menu.classList.toggle('hidden', !open);
        toggle.setAttribute('aria-expanded', String(open));
    };

    toggle.addEventListener('click', () => setOpen(menu.classList.contains('hidden')));
    menu.addEventListener('click', (e) => e.target.closest('a') && setOpen(false));
    document.addEventListener('keydown', (e) => e.key === 'Escape' && setOpen(false));
}
