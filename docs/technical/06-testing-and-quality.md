# 6. Testing and quality

## Test suite

```bash
php artisan test
```

Tests use an in-memory SQLite database (`phpunit.xml`) and a fake public disk, so they never touch your real data. To run them against MySQL:

```bash
DB_CONNECTION=mysql DB_HOST=127.0.0.1 DB_DATABASE=portfolio_test DB_USERNAME=... DB_PASSWORD=... php artisan test
```

| File | Covers |
|---|---|
| `tests/Feature/PortfolioTest.php` | Home and project pages, Download CV visibility, links section, HKI badge, drafts (404 vs. admin preview), admin access, sitemap/robots, WebP + small variant creation and cleanup, valid JSON-LD, 3D toggle, seeder guard |
| `tests/Feature/CustomizableContentTest.php` | Hidden features/tech/links/images/skills, featured projects and `/projects`, section order/visibility/texts, Text and Timeline sections (incl. HTML sanitizing), built-in section protection, free-text types, Duplicate action, admin forms (Livewire), export → import round trip |

22 tests pass on SQLite and on MySQL.

## Lighthouse (mobile)

Measured with gzip and cache headers (as configured in `deploy/nginx.conf` / `public/.htaccess`):

| Page | Performance | Accessibility | Best practices | SEO |
|---|---|---|---|---|
| Home | 98 | 100 | 100 | 100 |
| Project page | 100 | 100 | 100 | 100 |

What makes it fast: server-rendered HTML, 3D/GSAP/Lenis loaded on first interaction, `srcset` with small WebP images, self-hosted fonts with preload, lazy-loaded images below the fold, no layout shift (fixed image sizes).

Note: `php artisan serve` has no compression or caching, so Lighthouse scores locally are lower than in production.

## Accessibility

- Text colors meet WCAG AA, including project accent colors (`accent-fg` tokens).
- Skip link, landmark regions, labelled sections, alt texts from captions, `aria-label` on icon buttons, focus outlines.
- The lightbox is a native `<dialog>` with keyboard support; the 3D viewer is focusable with arrow-key control.
- `prefers-reduced-motion` turns off automatic motion, smooth scrolling, reveals and page transitions.

## Code style

PHP follows Laravel Pint defaults (`./vendor/bin/pint`). JavaScript is plain ES modules with short comments; one module per scene.
