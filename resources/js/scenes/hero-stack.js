// Hero scene around the phone: a floating browser window (web) and a glowing neural orb (AI),
// so the hero shows mobile, web and AI at a glance. Decorative only: no text content.
import * as THREE from 'three';
import { roundedBox, std, textTexture, float } from '../three/helpers';

const SCREEN = { w: 1.42, h: 0.86 };

export default function create({ group, accent, reduced, webScreens, pivot }) {
    const accentCss = `#${accent.getHexString()}`;

    // ---------- Browser window ----------
    const browser = new THREE.Group();
    browser.add(roundedBox(1.5, 0.94, 0.05, 0.06, std('#141a2e', { metalness: 0.35, roughness: 0.4 })));
    const canvas = document.createElement('canvas');
    canvas.width = 1024;
    canvas.height = Math.round((1024 * SCREEN.h) / SCREEN.w);
    const screenTex = new THREE.CanvasTexture(canvas);
    screenTex.colorSpace = THREE.SRGBColorSpace;
    screenTex.anisotropy = 4;
    const screen = new THREE.Mesh(
        new THREE.PlaneGeometry(SCREEN.w, SCREEN.h),
        new THREE.MeshBasicMaterial({ map: screenTex, toneMapped: false }),
    );
    screen.position.z = 0.031;
    browser.add(screen);
    drawBrowser(canvas, accentCss, null);
    screenTex.needsUpdate = true;

    // Web project screenshots replace the mock dashboard and rotate like the phone screens.
    const urls = webScreens ?? [];
    const images = [];
    const load = (i) => (images[i] ??= new Promise((resolve, reject) => {
        const img = new Image();
        img.onload = () => resolve(img);
        img.onerror = reject;
        img.src = urls[i];
    }));
    let webIndex = 0;
    let current = null;
    let fade = null;
    let nextWeb = 4;
    const showWeb = async (i) => {
        const n = urls.length;
        if (n < 2) return;
        const next = ((i % n) + n) % n;
        webIndex = next;
        const img = await load(next).catch(() => null);
        if (!img || webIndex !== next) return; // failed, or the visitor moved on
        fade = { from: current, to: img, start: null };
        load((next + 1) % n).catch(() => {});
    };
    if (urls.length) {
        load(0).then((img) => {
            current = img;
            drawBrowser(canvas, accentCss, img);
            screenTex.needsUpdate = true;
            if (urls.length > 1) load(1).catch(() => {});
        }).catch(() => {});
    }

    // ---------- AI orb ----------
    const ai = new THREE.Group();
    const core = new THREE.Mesh(
        new THREE.IcosahedronGeometry(0.15, 1),
        std(accent, { emissive: accent, emissiveIntensity: 0.35, flatShading: true, roughness: 0.35, metalness: 0.2 }),
    );
    const shell = new THREE.LineSegments(
        new THREE.EdgesGeometry(new THREE.IcosahedronGeometry(0.25, 1)),
        new THREE.LineBasicMaterial({ color: accent, transparent: true, opacity: 0.55 }),
    );
    ai.add(core, shell, glow(accentCss, 0.9));

    // Nodes on a sphere, linked to the core like a small neural network.
    const nodeCount = 9;
    const nodeMat = std(accent.clone().offsetHSL(0, 0, 0.2), { emissive: accent, emissiveIntensity: 0.4 });
    const nodeGeo = new THREE.SphereGeometry(0.025, 12, 8);
    const nodes = [];
    for (let i = 0; i < nodeCount; i++) {
        const y = 1 - (i / (nodeCount - 1)) * 2;
        const r = Math.sqrt(1 - y * y);
        const a = i * 2.399963; // golden angle
        const dir = new THREE.Vector3(Math.cos(a) * r, y, Math.sin(a) * r).multiplyScalar(0.4);
        const n = new THREE.Mesh(nodeGeo, nodeMat);
        n.userData.dir = dir;
        nodes.push(n);
        ai.add(n);
    }
    const linkPos = new Float32Array(nodeCount * 2 * 3);
    const linkGeo = new THREE.BufferGeometry();
    linkGeo.setAttribute('position', new THREE.BufferAttribute(linkPos, 3));
    ai.add(new THREE.LineSegments(linkGeo, new THREE.LineBasicMaterial({ color: accent, transparent: true, opacity: 0.35 })));

    // ---------- Code symbols ----------
    const symbol = (text) => {
        const tex = textTexture(text, { aspect: 1.6, bg: 'rgba(12,18,36,0.85)', fg: accentCss, scale: 0.46, radius: 0.32 });
        const s = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
        s.scale.set(0.3, 0.19, 1);
        return s;
    };
    const tag = symbol('</>');
    const braces = symbol('{ }');

    group.add(browser, ai, tag, braces);

    const place = (obj, x, y, z) => {
        obj.position.set(x, y, z);
        obj.userData.base = y;
    };

    // ---------- Phone ⇄ browser focus ----------
    // focus 0 = phone in front, 1 = browser in front. Swaps on its own every few seconds,
    // or right away when the item at the back is clicked / tapped.
    const AUTO = 7;
    const back = { x: -0.6, y: 0.12, z: -0.75, s: 1 };
    const front = { x: 0, y: 0.02, z: 0.45, s: 1 };
    const phoneBack = { x: 0.4, z: -1, s: 0.82 };
    let focus = 0;
    let target = 0;
    let nextSwitch = AUTO;
    let now = 0;
    let hovered = null;
    const lift = { browser: 0, phone: 0 };
    const lerp = THREE.MathUtils.lerp;

    const bring = (which) => {
        target = which === 'browser' ? 1 : 0;
        nextSwitch = now + AUTO * 1.7; // give the visitor time to look
    };
    const itemOf = (obj) => {
        for (let o = obj; o; o = o.parent) {
            if (o === browser) return 'browser';
            if (pivot && o === pivot) return 'phone';
        }
        return null;
    };
    const atBack = (item) => (item === 'browser' ? target === 0 : item === 'phone' && target === 1);

    return {
        clickables: [browser, pivot].filter(Boolean),
        onClick(obj) {
            const item = itemOf(obj);
            if (atBack(item)) bring(item);
        },
        /** Returns true when the hovered item can be clicked (it is at the back). */
        onHover(obj) {
            hovered = obj ? itemOf(obj) : null;
            return atBack(hovered);
        },
        /** Quick swipe while the browser is in front: next (1) or previous (-1) web screen. */
        flick(dir) {
            nextWeb = now + 6;
            showWeb(webIndex + dir);
        },
        /** Visitor is touching / dragging: postpone the automatic swap. */
        onInteract() {
            nextSwitch = Math.max(nextSwitch, now + AUTO);
        },
        /** True while the browser is in front: drags turn it, and swipes don't change the phone screen. */
        ownsDrag() {
            return target === 1;
        },
        toggle() {
            bring(target ? 'phone' : 'browser');
        },
        layout({ left, right, distance }) {
            // Less room on a side (phones, narrow screens) → smaller illustrations on that side.
            const kl = THREE.MathUtils.clamp(left / 1.3, 0.6, 1);
            const kr = THREE.MathUtils.clamp(right / 1.3, 0.55, 1);
            ai.scale.setScalar(kr);
            tag.scale.set(0.3 * kl, 0.19 * kl, 1);
            braces.scale.set(0.3 * kr, 0.19 * kr, 1);
            place(ai, Math.min(right * 0.66, right - 0.52 * kr), 0.8, 0.1);
            place(tag, -(left - 0.28 * kl), -0.84, 0.25);
            place(braces, right - 0.26 * kr, -0.56, 0.15);

            // Back: behind the phone, sticking out on the left.
            Object.assign(back, { x: -Math.max(0.35, left - 0.72 * kl), s: kl });
            // Front: left-aligned and ~3/4 of the room at that depth, so the phone still peeks out on the right.
            const D = distance || 6;
            const near = (D - front.z) / D;
            front.s = Math.min(1.15, ((left + right) * near * 0.74) / 1.5);
            front.x = -left * near + (1.5 * front.s) / 2 + 0.04;
            // Phone steps back to the right while the browser is in front.
            const far = (D - phoneBack.z) / D;
            phoneBack.x = Math.max(0.2, right * far - 0.5 * phoneBack.s - 0.05);
        },
        update(t, dt = 0.016) {
            now = t;
            const m = reduced ? 0 : 1;
            if (!reduced && t > nextSwitch) {
                target = 1 - target;
                nextSwitch = t + AUTO;
            }
            focus = reduced ? target : focus + (target - focus) * Math.min(1, dt * 2.5);

            // Web screens: auto-advance, cross-fading on the browser's canvas.
            if (!reduced && urls.length > 1 && !fade && t > nextWeb) {
                nextWeb = t + 4;
                showWeb(webIndex + 1);
            }
            if (fade) {
                fade.start ??= t;
                const k = reduced ? 1 : Math.min(1, (t - fade.start) / 0.45);
                drawBrowser(canvas, accentCss, fade.to, fade.from, k);
                screenTex.needsUpdate = true;
                if (k >= 1) {
                    current = fade.to;
                    fade = null;
                }
            }
            const arc = Math.sin(Math.PI * focus);
            for (const item of ['browser', 'phone']) {
                lift[item] += ((hovered === item && atBack(item) ? 1 : 0) - lift[item]) * 0.15;
            }

            const lb = lift.browser;
            browser.position.x = lerp(back.x, front.x, focus);
            browser.position.z = lerp(back.z, front.z, focus) + lb * 0.15;
            float(browser, t, { base: lerp(back.y, front.y, focus) + arc * 0.18 + lb * 0.06, amp: 0.04 * m, speed: 0.6 });
            browser.scale.setScalar(lerp(back.s, front.s, focus) * (1 + lb * 0.04));
            // Drag / swipe / hover tilt (set on the pivot by the viewer) turns whichever item is in front.
            const rx = pivot?.rotation.x ?? 0;
            const ry = pivot?.rotation.y ?? 0;
            browser.rotation.set(
                lerp(-0.04, -0.04 + rx, focus),
                lerp(0.38 + Math.sin(t * 0.35) * 0.06 * m, 0.1 + ry, focus),
                0,
            );

            if (pivot) {
                pivot.rotation.set(rx * (1 - focus), ry * (1 - focus), 0);
                const lp = lift.phone;
                pivot.position.x = lerp(0, phoneBack.x, focus);
                pivot.position.z = lerp(0, phoneBack.z, focus) - arc * 0.2 + lp * 0.15;
                pivot.scale.setScalar(lerp(1, phoneBack.s, focus) * (1 + lp * 0.03));
            }

            float(ai, t, { base: ai.userData.base ?? 0, amp: 0.05 * m, speed: 0.9, phase: 1 });
            float(tag, t, { base: tag.userData.base ?? 0, amp: 0.05 * m, speed: 1.1, phase: 2 });
            float(braces, t, { base: braces.userData.base ?? 0, amp: 0.05 * m, speed: 1, phase: 3 });

            core.rotation.set(t * 0.4 * m, t * 0.6 * m, 0);
            shell.rotation.set(-t * 0.2 * m, -t * 0.3 * m, 0);
            core.scale.setScalar(1 + Math.sin(t * 2.2) * 0.06 * m);

            // Nodes breathe in and out; links follow them.
            nodes.forEach((n, i) => {
                const pulse = 1 + Math.sin(t * 1.6 + i) * 0.12 * m;
                n.position.copy(n.userData.dir).multiplyScalar(pulse).applyAxisAngle(THREE.Object3D.DEFAULT_UP, t * 0.35 * m);
                linkPos.set([0, 0, 0, n.position.x, n.position.y, n.position.z], i * 6);
            });
            linkGeo.attributes.position.needsUpdate = true;
        },
    };
}

/** Soft additive glow sprite behind the orb. */
function glow(color, size) {
    const c = document.createElement('canvas');
    c.width = c.height = 128;
    const g = c.getContext('2d');
    const grd = g.createRadialGradient(64, 64, 0, 64, 64, 64);
    grd.addColorStop(0, color);
    grd.addColorStop(1, 'rgba(0,0,0,0)');
    g.fillStyle = grd;
    g.fillRect(0, 0, 128, 128);
    const s = new THREE.Sprite(new THREE.SpriteMaterial({
        map: new THREE.CanvasTexture(c), transparent: true, opacity: 0.3, depthWrite: false, blending: THREE.AdditiveBlending,
    }));
    s.scale.set(size, size, 1);
    return s;
}

/**
 * Browser chrome plus either a screenshot (full width, top-aligned) or a mock dashboard.
 * With `under`, the screenshot fades in over the previous one at `alpha`.
 */
function drawBrowser(c, accent, image, under = null, alpha = 1) {
    const g = c.getContext('2d');
    const W = c.width;
    const H = c.height;
    const bar = 64;
    g.fillStyle = '#0d1326';
    g.fillRect(0, 0, W, H);

    // Title bar: traffic lights + address pill
    g.fillStyle = '#1a2140';
    g.fillRect(0, 0, W, bar);
    ['#ff5f57', '#febc2e', '#28c840'].forEach((col, i) => {
        g.fillStyle = col;
        g.beginPath();
        g.arc(34 + i * 30, bar / 2, 9, 0, Math.PI * 2);
        g.fill();
    });
    g.fillStyle = '#0d1326';
    g.beginPath();
    g.roundRect(150, 16, W - 300, bar - 32, 16);
    g.fill();

    if (image) {
        if (under) g.drawImage(under, 0, bar, W, (under.height * W) / under.width);
        g.globalAlpha = alpha;
        g.drawImage(image, 0, bar, W, (image.height * W) / image.width);
        g.globalAlpha = 1;
        return;
    }

    const pad = 28;
    const top = bar + pad;
    // Sidebar
    g.fillStyle = '#141b33';
    g.beginPath();
    g.roundRect(pad, top, 170, H - top - pad, 18);
    g.fill();
    g.fillStyle = accent;
    g.beginPath();
    g.roundRect(pad + 22, top + 24, 126, 22, 11);
    g.fill();
    g.fillStyle = 'rgba(255,255,255,0.14)';
    for (let i = 0; i < 5; i++) {
        g.beginPath();
        g.roundRect(pad + 22, top + 74 + i * 44, 100 + (i % 2) * 20, 16, 8);
        g.fill();
    }

    // KPI cards
    const x0 = pad + 170 + pad;
    const cw = (W - x0 - pad - pad * 2) / 3;
    for (let i = 0; i < 3; i++) {
        const x = x0 + i * (cw + pad);
        g.fillStyle = '#141b33';
        g.beginPath();
        g.roundRect(x, top, cw, 110, 18);
        g.fill();
        g.fillStyle = 'rgba(255,255,255,0.16)';
        g.beginPath();
        g.roundRect(x + 20, top + 22, cw * 0.45, 14, 7);
        g.fill();
        g.fillStyle = i === 0 ? accent : 'rgba(255,255,255,0.7)';
        g.beginPath();
        g.roundRect(x + 20, top + 54, cw * 0.6, 32, 10);
        g.fill();
    }

    // Bar chart
    const cy = top + 110 + pad;
    const ch = H - cy - pad;
    const chartW = W - x0 - pad;
    g.fillStyle = '#141b33';
    g.beginPath();
    g.roundRect(x0, cy, chartW, ch, 18);
    g.fill();
    const bars = [0.45, 0.7, 0.55, 0.85, 0.6, 0.95, 0.75, 0.5, 0.8];
    const bw = (chartW - 60) / bars.length;
    bars.forEach((v, i) => {
        const bh = (ch - 60) * v;
        g.fillStyle = i === 5 ? accent : 'rgba(255,255,255,0.22)';
        g.beginPath();
        g.roundRect(x0 + 30 + i * bw + bw * 0.2, cy + ch - 30 - bh, bw * 0.6, bh, 8);
        g.fill();
    });
}
