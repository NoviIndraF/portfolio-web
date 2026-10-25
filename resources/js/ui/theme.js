// Light / dark mode. The inline script in <head> sets data-theme before paint;
// this keeps the toggle, the browser bar color and the visitor's choice in sync.
// Other modules (3D particles) listen for the "themechange" event.
const root = document.documentElement;

export const currentTheme = () => (root.dataset.theme === 'light' ? 'light' : 'dark');

function apply(theme) {
    root.dataset.theme = theme;
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = getComputedStyle(root).getPropertyValue('--c-bg').trim();
    document.querySelectorAll('[data-theme-toggle]').forEach((b) => b.setAttribute('aria-pressed', String(theme === 'dark')));
    document.dispatchEvent(new CustomEvent('themechange', { detail: theme }));
}

const saved = () => {
    try {
        return localStorage.getItem('theme');
    } catch {
        return null;
    }
};

export function initTheme() {
    apply(currentTheme());

    document.addEventListener('click', (e) => {
        if (!e.target.closest('[data-theme-toggle]')) return;
        const theme = currentTheme() === 'light' ? 'dark' : 'light';
        try {
            localStorage.setItem('theme', theme);
        } catch {
            /* private mode: the choice lasts for this page only */
        }
        apply(theme);
    });

    // Follow the device while the visitor has not picked a mode themselves.
    matchMedia('(prefers-color-scheme: light)').addEventListener('change', (e) => {
        if (!saved() && root.dataset.defaultTheme === 'system') apply(e.matches ? 'light' : 'dark');
    });
}
