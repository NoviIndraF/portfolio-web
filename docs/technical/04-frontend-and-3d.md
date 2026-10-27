# 4. Frontend and 3D

## Views (`resources/views`)

| Path | Role |
|---|---|
| `components/layouts/site.blade.php` | `<x-layouts.site>`: `<head>` (title, description, canonical, Open Graph, Twitter card, JSON-LD `Person`), theme CSS variables from Settings, `@fonts`, `@vite`, header, footer. Props: `title`, `description`, `image`, `accent`. |
| `components/site/header.blade.php` | Logo, menu built from `Site::nav()` (visible sections with a menu label), Download CV, mobile menu. |
| `components/site/phone.blade.php` | Static phone frame with swipeable screenshots. Also the mount point of the 3D viewer (see `data-*` below) and its fallback. |
| `components/site/phone-placeholder.blade.php` | Screen for projects without screenshots (app name + gauges or bars). |
| `components/site/project-card.blade.php` | Card used in the carousel and on `/projects`. |
| `components/site/gallery.blade.php`, `lightbox.blade.php` | Gallery grouped by image kind + one shared `<dialog>` lightbox. Gallery items are real links, so they work without JavaScript. |
| `components/site/tech-chip.blade.php`, `section-heading.blade.php`, `linkedin-icon.blade.php` | Small building blocks. |
| `sections/*.blade.php` | `hero` (always first) and one partial per section type: `projects`, `skills`, `about`, `contact`, `text`, `timeline`. `pages/home` loops over `$sections` and `@includeIf('sections.'.$type)`. |
| `pages/home`, `pages/projects`, `pages/project` | The three public pages. |
| `errors/404.blade.php`, `seo/sitemap.blade.php` | 404 page and sitemap. |

All interface text uses `__('site.*')` from `lang/en/site.php`.

> Blade tip learned the hard way: inside `{{ }}`/`{!! !!}` Blade treats `@context` as a directive. Build arrays with `@`-keys (JSON-LD) inside a `@php` block. Also avoid mixing one-line `@php(...)` with `@php ... @endphp` blocks in the same file.

## CSS (`resources/css/app.css`)

Tailwind 4 with theme tokens that read CSS variables set by the layout from Settings:

| Token | Source |
|---|---|
| `bg`, `fg`, `primary` | `--c-bg`, `--c-text`, `--c-primary` (Settings) |
| `muted`, `surface`, `surface-2`, `line` | `color-mix()` of text and background |
| `accent` | `--accent` set per project (`style="--accent: #…"`), falls back to primary |
| `accent-fg`, `primary-fg` | Lighter versions for text, so dark accents stay readable (WCAG AA) |

Components: `.container-site`, `.eyebrow`, `.btn-primary`, `.btn-ghost`, `.chip`, `.card`, `.phone`, `.phone-canvas`, `.prose-site`. When using an accent in raw CSS, write `var(--accent, var(--c-primary))`, not `var(--color-accent)` (that one is resolved on `:root`).

## JavaScript modules (`resources/js`)

`app.js` loads small modules immediately and defers the heavy ones:

| Module | Loaded | Purpose |
|---|---|---|
| `ui/nav.js` | immediately | Mobile menu |
| `ui/rail.js` | immediately | Scroll-snap rails: arrow buttons, mouse drag |
| `ui/phone-screens.js` | immediately | Dots for the static phone screens, arrow keys |
| `ui/lightbox.js` | immediately | Gallery viewer: keyboard, swipe, pinch / wheel / double-tap zoom, pan |
| `ui/carousel3d.js` | immediately | Coverflow transform on the project rail, card tilt, View Transition names, skills spotlight |
| `ui/filter.js` | immediately | Type filter on `/projects` |
| `ui/interactions.js` → `initPhotoCarousel` | immediately | About gallery: dots follow the scroll position, auto-advance every 5 s (wraps). Pauses on hover/focus or while the lightbox is open, stops after a touch, runs only while visible. No auto-advance with `prefers-reduced-motion`. |
| `ui/three-boot.js` | immediately (tiny) | Decides if 3D runs; on the first interaction lazy-imports the viewer / hero scene for elements near the viewport |
| `ui/motion.js` | first interaction | Lenis (mouse/trackpad only), reveal animations (`data-reveal`, `data-reveal-stagger`) via IntersectionObserver + GSAP, light parallax via ScrollTrigger |
| `three/*`, `scenes/*` | first interaction | 3D (separate chunks) |

### Scroll companion (`scenes/scroll-ornament.js`)
One fixed, click-through canvas on the home page (`data-scene="scroll-ornament"`, `data-sections` = `{anchor: type}` of the visible sections). A particle cloud (1200 points, 500 on phones) sits at the upper right beside the section headings and morphs per section type: `about` orb, `timeline` layers, `projects` faint small orb, `skills` network, `contact` dot; unknown types use the orb. Hidden over the hero. It sleeps when invisible and the page is still, and wakes on scroll. Not loaded with `prefers-reduced-motion` or when 3D is off. To change a look, edit `LOOKS` at the top of the file.

### When 3D does not run

`three-boot.js` keeps the static phone (adds `html.no-3d`) when Settings → 3D is off (`data-3d="off"`), WebGL is unavailable, or `navigator.connection.saveData` is on. The viewer also falls back if the first screenshot fails to load or the WebGL context is lost. With `prefers-reduced-motion`, 3D still loads but has no automatic motion, and `motion.js` does nothing.

## PhoneViewer3D (`resources/js/three/phone-viewer.js`)

Mounted on every element with `data-phone-viewer` (rendered by `components/site/phone.blade.php`):

| Attribute | Meaning |
|---|---|
| `data-scene` | `hero` or a project `scene_type` |
| `data-accent` | Project color |
| `data-title` | App name (placeholder screen, aria-label) |
| `data-screens` | JSON array of screenshot URLs (empty → placeholder screen) |
| `data-model` | Optional `.glb` URL |

How it works:
- The phone is built from primitives in `three/phone-model.js` (extruded rounded body, two stacked screen meshes for cross-fading, island, buttons, camera bump).
- Screenshots are loaded as textures and fitted like `object-fit: cover; object-position: top` (`three/screen-textures.js`). Projects without screenshots get an animated canvas texture.
- The canvas is sized so the 3D phone has exactly the on-screen size of the static phone; the canvas sits behind nearby text (`z-index: -1`) and input is handled on the viewer element.
- **Gestures:** drag = rotate (eases back), quick horizontal flick = next/previous screen, pinch or ctrl/trackpad-pinch = zoom, mouse hover / Android gyroscope = tilt, arrow keys = screens, `+`/`-` = zoom. Click on scene objects is raycast and forwarded to the scene (`onClick`).
- Rendering pauses when off-screen or when the tab is hidden. Pixel ratio is capped (1.5 on phones).
- For debugging: `document.querySelector('[data-phone-viewer]').phoneViewer`.

## Accent scenes (`resources/js/scenes`)

One module per `scene_type`, registered in `scenes/index.js` and loaded on demand:

```js
// resources/js/scenes/my-scene.js
import * as THREE from 'three';
import { std, float } from '../three/helpers';

export default function create({ group, accent, accentHex, reduced, mobile, model }) {
    const cube = new THREE.Mesh(new THREE.BoxGeometry(0.3, 0.3, 0.3), std(accent));
    group.add(cube);

    return {
        // Called on every resize. `spread` = usable half-width (world units) beside the phone,
        // `front` = a z offset that keeps objects in front on wide screens and behind on narrow ones.
        layout({ spread, front }) {
            cube.position.set(spread * 0.8, 0.5, front);
        },
        update(t, dt) {
            float(cube, t, { base: 0.5, amp: 0.05 });
            cube.rotation.y = t;
        },
        clickables: [cube],          // optional
        onClick(object) {},          // optional
    };
}
```

To add a scene: create the module, add it to `scenes/index.js` and to `Project::SCENE_TYPES` (so it appears in the admin). `model` is the uploaded `.glb` (normalised to ~0.8 units) or `null`.

Existing scenes: `learning` (books, code blocks, clickable quiz cube), `donation` (pins with coins and hearts flowing between them), `shoe-store` (sneaker on a pedestal or the uploaded model, chat bubbles), `chat` (network nodes with travelling messages), `health-iot` (beating heart, ECG line, circuit board, gauges), `hydroponic` (pipes, plants, drops, sensor), `analytics` (bar chart and radar, neutral), `generic` (floating shapes), `hero.js` (pointer-reactive particles behind the hero), `scroll-ornament.js` (see above).

`three/helpers.js` provides `std()`, `roundedRectShape()`, `roundedBox()`, `shapeWithUv()`, `heart()`, `textTexture()`, `label()`, `chatBubble()`, `float()`, `ease`, `disposeTree()`, `softSprite()` (round particle texture).

## Page transitions

`@view-transition { navigation: auto; }` in CSS. When a card link is clicked, `carousel3d.js` names the card's phone `project-phone` and its title `project-title`; the project page uses the same names, so supporting browsers morph them. Disabled for reduced motion.
