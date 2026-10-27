// Home page companion: one particle cloud behind the content that morphs per section while scrolling.
// One fixed canvas (one WebGL context) for the whole page. Mounted by ui/three-boot.js.
import * as THREE from 'three';
import { softSprite } from '../three/helpers';

/** Look per section type: shape, opacity, scale. Unknown types fall back to the orb. */
const LOOKS = {
    hero: { shape: 'orb', opacity: 0, scale: 0.6 },
    about: { shape: 'orb', opacity: 0.7, scale: 1 },
    timeline: { shape: 'layers', opacity: 0.65, scale: 0.9 },
    projects: { shape: 'orb', opacity: 0.15, scale: 0.6 },
    skills: { shape: 'network', opacity: 0.7, scale: 0.85 },
    contact: { shape: 'dot', opacity: 0.9, scale: 1.6 },
};
const fallbackLook = { shape: 'orb', opacity: 0.45, scale: 0.9 };

/** Target positions for every shape, same point count so they can morph into each other. */
export function buildShapes(n) {
    const make = (fn) => {
        const a = new Float32Array(n * 3);
        for (let i = 0; i < n; i++) a.set(fn(i), i * 3);
        return a;
    };
    const golden = Math.PI * (3 - Math.sqrt(5));
    const sphere = (i, count, r) => {
        const y = 1 - (i / Math.max(count - 1, 1)) * 2;
        const ring = Math.sqrt(1 - y * y);
        return [Math.cos(golden * i) * ring * r, y * r, Math.sin(golden * i) * ring * r];
    };
    const hubs = Array.from({ length: 7 }, (_, h) => sphere(h, 7, 1.7));
    return {
        orb: make((i) => sphere(i, n, 1.6)),
        layers: make((i) => {
            const layer = i % 4;
            const a = (i / n) * Math.PI * 2 * 9;
            const r = 1.5 - layer * 0.15 + Math.sin(i) * 0.05;
            return [Math.cos(a) * r, -1.05 + layer * 0.7, Math.sin(a) * r];
        }),
        network: make((i) => {
            const [hx, hy, hz] = hubs[i % hubs.length];
            const [sx, sy, sz] = sphere(Math.floor(i / hubs.length), Math.ceil(n / hubs.length), 0.32);
            return [hx + sx, hy + sy, hz + sz];
        }),
        dot: make((i) => sphere(i, n, 0.18)),
    };
}

const smooth = (a, b, x) => {
    const t = Math.min(Math.max((x - a) / (b - a), 0), 1);
    return t * t * (3 - 2 * t);
};

export default function mountScrollOrnament(el, { mobile = false, color = '#4c8dff' } = {}) {
    const map = JSON.parse(el.dataset.sections || '{}');
    const stops = [{ el: document.querySelector('[aria-labelledby="hero-title"]'), look: LOOKS.hero }]
        .concat(Object.entries(map).map(([anchor, type]) => ({ el: document.getElementById(anchor), look: LOOKS[type] ?? fallbackLook })))
        .filter((s) => s.el);
    if (stops.length < 2) return;

    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: false, powerPreference: 'low-power' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, mobile ? 1 : 1.5));
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block';
    el.appendChild(renderer.domElement);
    let alive = true;
    renderer.domElement.addEventListener('webglcontextlost', (e) => {
        e.preventDefault();
        alive = false;
        renderer.domElement.remove();
    });

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 50);
    camera.position.z = 8;

    const N = mobile ? 500 : 1200;
    const shapes = buildShapes(N);
    const pos = new Float32Array(shapes.orb);
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));
    const mat = new THREE.PointsMaterial({ color, size: mobile ? 0.08 : 0.075, map: softSprite(), transparent: true, depthWrite: false, opacity: 0 });
    const group = new THREE.Group();
    group.add(new THREE.Points(geo, mat));
    scene.add(group);

    let light = false;
    const setTheme = (theme) => {
        light = theme === 'light';
        mat.blending = light ? THREE.NormalBlending : THREE.AdditiveBlending;
        mat.needsUpdate = true;
    };
    document.addEventListener('themechange', (e) => setTheme(e.detail));
    setTheme(document.documentElement.dataset.theme);

    let halfW = 1;
    let halfH = 1;
    const resize = () => {
        const w = innerWidth;
        const h = innerHeight;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        halfH = Math.tan(THREE.MathUtils.degToRad(22.5)) * camera.position.z;
        halfW = halfH * camera.aspect;
    };
    addEventListener('resize', resize, { passive: true });
    resize();

    /** Current stop index and how far (0–1) we are morphing into the next one. */
    const progress = () => {
        const y = innerHeight * 0.5;
        let i = 0;
        for (let k = 0; k < stops.length; k++) {
            if (stops[k].el.getBoundingClientRect().top <= y) i = k;
        }
        const r = stops[i].el.getBoundingClientRect();
        const p = (y - r.top) / Math.max(r.height, 1);
        return { i, e: i < stops.length - 1 ? smooth(0.6, 1, p) : 0 };
    };

    // Upper right, beside the left-aligned section headings (cards below are opaque).
    // It drifts between two spots per section; phones keep it centred and fainter.
    const sideX = (i) => (mobile ? 0 : halfW * (i % 2 ? 0.62 : 0.42));
    const topY = () => halfH * (mobile ? 0.4 : 0.3);
    const lerp = (a, b, t) => a + (b - a) * t;

    const clock = new THREE.Clock();
    let running = false;
    let idleFrames = 0;
    let lastY = -1;
    const frame = () => {
        if (!alive || document.hidden) {
            running = false;
            return;
        }
        const dt = Math.min(clock.getDelta(), 0.05);
        const { i, e } = progress();
        const a = stops[i].look;
        const b = (stops[i + 1] ?? stops[i]).look;
        const from = shapes[a.shape];
        const to = shapes[b.shape];

        for (let k = 0; k < pos.length; k++) {
            pos[k] += (lerp(from[k], to[k], e) - pos[k]) * 0.12;
        }
        geo.attributes.position.needsUpdate = true;

        const opacity = lerp(a.opacity, b.opacity, e) * (mobile ? 0.6 : 1) * (light ? 0.8 : 1);
        mat.opacity += (opacity - mat.opacity) * 0.1;
        group.scale.setScalar(lerp(a.scale, b.scale, e));
        group.position.x += (lerp(sideX(i), sideX(i + 1), e) - group.position.x) * 0.08;
        group.position.y = topY();
        group.rotation.y += dt * 0.12;
        group.rotation.x = Math.sin(scrollY * 0.0006) * 0.35;

        renderer.render(scene, camera);

        // Sleep when nothing is visible and the page is still; the scroll listener wakes it.
        idleFrames = mat.opacity < 0.01 && scrollY === lastY ? idleFrames + 1 : 0;
        lastY = scrollY;
        if (idleFrames > 30) {
            running = false;
            return;
        }
        requestAnimationFrame(frame);
    };
    const wake = () => {
        if (alive && !document.hidden && !running) {
            running = true;
            idleFrames = 0;
            clock.getDelta();
            requestAnimationFrame(frame);
        }
    };
    addEventListener('scroll', wake, { passive: true });
    document.addEventListener('visibilitychange', wake);
    wake();
}
