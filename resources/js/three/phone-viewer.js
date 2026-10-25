// PhoneViewer3D: reusable 3D phone showing a project's screenshots, with its accent scene around it.
// Gestures: drag to rotate, quick swipe (or arrow keys / dots) to change screen,
// pinch or ctrl/trackpad-pinch to zoom, pointer/gyro tilt. Static phone stays as the fallback.
import * as THREE from 'three';
import { RoomEnvironment } from 'three/addons/environments/RoomEnvironment.js';
import { createPhone, PHONE } from './phone-model';
import { loadScreen, placeholderScreen } from './screen-textures';
import { loadScene } from '../scenes';

const clamp = (v, a, b) => Math.min(b, Math.max(a, v));

export class PhoneViewer3D {
    constructor(root, { reduced = false, mobile = false } = {}) {
        this.root = root;
        root.phoneViewer = this; // handy for debugging in the console
        this.reduced = reduced;
        this.mobile = mobile;
        this.staticEl = root.querySelector('[data-phone-static]');
        this.dots = [...root.querySelectorAll('[data-screen-dots] > span')];
        this.sceneType = root.dataset.scene || '';
        this.isHero = this.sceneType === 'hero';
        this.accentHex = root.dataset.accent || getComputedStyle(document.documentElement).getPropertyValue('--c-primary').trim() || '#4c8dff';
        this.title = root.dataset.title || '';
        this.urls = JSON.parse(root.dataset.screens || '[]');
        this.modelUrl = root.dataset.model || '';

        this.index = 0;
        this.textures = [];
        this.rot = { x: 0, y: 0 };
        this.target = { x: 0, y: 0 };
        this.hover = { x: 0, y: 0 };
        this.zoom = 1;
        this.targetZoom = 1;
        this.fade = null;
        this.lastInteraction = 0;
        this.visible = true;
        this.clock = new THREE.Clock();

        this.initRenderer();
        this.initScene();
        this.bindEvents();
        this.resize();
        this.initScreens().then(() => this.root.classList.add('is-3d')).catch(() => {});
        this.initAccentScene();
        this.loop = this.loop.bind(this);
        this.running = true;
        requestAnimationFrame(this.loop);
    }

    initRenderer() {
        this.holder = document.createElement('div');
        this.holder.className = 'phone-canvas';
        this.holder.setAttribute('aria-hidden', 'true');
        // Hero: the canvas reaches past the phone, so it takes input for the items beside it.
        // It stays inside the phone column, so lifting it above the layout never covers the hero text.
        if (this.isHero) Object.assign(this.holder.style, { pointerEvents: 'auto', zIndex: '0' });
        this.root.prepend(this.holder);

        this.renderer = new THREE.WebGLRenderer({ antialias: !this.mobile, alpha: true, powerPreference: 'high-performance' });
        this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, this.mobile ? 1.5 : 2));
        this.renderer.outputColorSpace = THREE.SRGBColorSpace;
        this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
        this.holder.appendChild(this.renderer.domElement);
        // If the GPU drops the context (low memory, driver reset), fall back to the static phone.
        this.renderer.domElement.addEventListener('webglcontextlost', (e) => {
            e.preventDefault();
            this.destroy();
        });

        // The root (not the canvas) receives input, so the canvas can sit behind nearby text.
        this.root.tabIndex = 0;
        this.root.setAttribute('role', 'group');
        this.root.setAttribute('aria-roledescription', '3D phone');
        this.root.setAttribute('aria-label', `${this.title || 'App'} preview. Drag to rotate, swipe or use arrow keys to change screen.`);
    }

    initScene() {
        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(30, 1, 0.1, 60);

        const pmrem = new THREE.PMREMGenerator(this.renderer);
        this.scene.environment = pmrem.fromScene(new RoomEnvironment(), 0.04).texture;
        pmrem.dispose();

        this.scene.add(new THREE.HemisphereLight('#c8d6ff', '#0b1020', 0.8));
        const key = new THREE.DirectionalLight('#ffffff', 1.6);
        key.position.set(2, 3, 4);
        this.scene.add(key);
        const rim = new THREE.PointLight(this.accentHex, 6, 8);
        rim.position.set(-1.5, 0.5, -1.5);
        this.scene.add(rim);

        this.pivot = new THREE.Group(); // rotated by gestures
        this.scene.add(this.pivot);
        this.phone = createPhone();
        this.pivot.add(this.phone.group);

        this.sceneGroup = new THREE.Group(); // accent scene (not rotated with the phone)
        this.scene.add(this.sceneGroup);
        this.raycaster = new THREE.Raycaster();
    }

    // ---------- Screens ----------
    async initScreens() {
        const { screenA, screenAspect } = this.phone;
        if (!this.urls.length) {
            this.placeholder = placeholderScreen({ title: this.title, accent: this.accentHex, scene: this.sceneType });
            screenA.material.map = this.placeholder.texture;
            screenA.material.needsUpdate = true;
            return;
        }
        try {
            this.textures[0] = await loadScreen(this.urls[0], screenAspect, this.renderer);
            screenA.material.map = this.textures[0];
            screenA.material.needsUpdate = true;
            this.preload(1);
        } catch {
            // Keep the static fallback if the first screen can't load.
            this.destroy();
            throw new Error('screen load failed');
        }
    }

    async texture(i) {
        if (!this.textures[i]) this.textures[i] = loadScreen(this.urls[i], this.phone.screenAspect, this.renderer);
        return this.textures[i];
    }

    preload(i) {
        if (i < this.urls.length) this.texture(i).catch(() => {});
    }

    async show(i) {
        const n = this.urls.length;
        if (n < 2) return;
        const next = (i + n) % n;
        if (next === this.index) return;
        this.index = next;
        this.updateDots();
        this.root.dispatchEvent(new CustomEvent('phone:screen', { detail: { index: next } }));
        const tex = await this.texture(next);
        if (this.index !== next) return; // user moved on
        const { screenA, screenB } = this.phone;
        screenB.material.map = tex;
        screenB.material.needsUpdate = true;
        this.fade = { start: this.clock.elapsedTime, tex, a: screenA, b: screenB };
        this.preload(next + 1);
    }

    updateDots() {
        this.dots.forEach((d, n) => {
            const on = n === this.index;
            d.classList.toggle('w-5', on);
            d.classList.toggle('bg-accent', on);
            d.classList.toggle('w-1.5', !on);
            d.classList.toggle('bg-fg/25', !on);
        });
    }

    // ---------- Accent scene ----------
    async initAccentScene() {
        if (!this.sceneType) return;
        const create = await loadScene(this.sceneType);
        if (!create) return;
        let model = null;
        if (this.modelUrl) model = await this.loadModel(this.modelUrl).catch(() => null);
        this.ctx = {
            group: this.sceneGroup,
            accent: new THREE.Color(this.accentHex),
            accentHex: this.accentHex,
            reduced: this.reduced,
            mobile: this.mobile,
            webScreens: JSON.parse(this.root.dataset.webScreens || '[]'),
            pivot: this.pivot,
            model,
        };
        this.accent = create(this.ctx);
        this.layoutScene();
    }

    async loadModel(url) {
        const { GLTFLoader } = await import('three/addons/loaders/GLTFLoader.js');
        const gltf = await new GLTFLoader().loadAsync(url);
        const obj = gltf.scene;
        // Normalize size to ~0.8 units.
        const box = new THREE.Box3().setFromObject(obj);
        const size = box.getSize(new THREE.Vector3()).length() || 1;
        obj.scale.setScalar(0.8 / size);
        box.setFromObject(obj);
        obj.position.sub(box.getCenter(new THREE.Vector3()));
        const wrap = new THREE.Group();
        wrap.add(obj);
        return wrap;
    }

    layoutScene() {
        if (!this.accent?.layout) return;
        // Half-width of the visible area at the phone's depth, minus a margin.
        const halfH = Math.tan(THREE.MathUtils.degToRad(this.camera.fov / 2)) * this.camera.position.z;
        const halfW = halfH * this.camera.aspect;
        const spread = Math.max(0.78, halfW - 0.32);
        // Hero canvas can be off-center: visible world width on each side of the phone.
        const perPx = (halfH * 2) / (this.extent?.h || 1);
        const left = this.extent ? this.extent.left * perPx : halfW;
        const right = this.extent ? this.extent.right * perPx : halfW;
        this.accent.layout({ spread, front: spread > 1.05 ? 0.25 : -0.35, left, right, distance: this.camera.position.z });
    }

    // ---------- Sizing ----------
    resize() {
        const phoneRect = this.staticEl.getBoundingClientRect();
        const rootRect = this.root.getBoundingClientRect();
        if (!phoneRect.height) return;
        const pad = 40;
        const h = phoneRect.height + pad * 2;
        let w = Math.min(window.innerWidth, 736);
        let full = w;
        this.extent = null;
        if (this.isHero) {
            // Hero: room for the browser and AI illustrations on each side of the phone,
            // without covering the hero text or going past the screen edge.
            const cx = phoneRect.left + phoneRect.width / 2;
            const col = this.root.parentElement.getBoundingClientRect();
            const want = phoneRect.width * 1.4;
            const min = phoneRect.width * 0.6;
            const left = Math.round(Math.min(want, Math.max(min, cx - col.left)));
            const right = Math.round(Math.min(want, Math.max(min, document.documentElement.clientWidth - 8 - cx)));
            this.extent = { left, right, h };
            w = left + right;
            full = 2 * Math.max(left, right);
            Object.assign(this.holder.style, { left: `${cx - left - rootRect.left}px`, transform: 'none' });
        }
        Object.assign(this.holder.style, {
            width: `${w}px`,
            height: `${h}px`,
            top: `${phoneRect.top - rootRect.top - pad}px`,
        });
        this.renderer.setSize(w, h, false);
        this.camera.aspect = full / h;
        // Render only the part of a phone-centered view that the (possibly off-center) canvas covers.
        if (this.extent) {
            this.camera.setViewOffset(full, h, full / 2 - this.extent.left, 0, w, h);
        }
        // Same on-screen size as the static phone, so the swap is seamless.
        const worldH = (PHONE.height * h) / phoneRect.height;
        this.baseDistance = worldH / 2 / Math.tan(THREE.MathUtils.degToRad(this.camera.fov / 2));
        this.camera.position.set(0, 0, this.baseDistance / this.zoom);
        this.camera.updateProjectionMatrix();
        this.layoutScene();
    }

    // ---------- Input ----------
    bindEvents() {
        // Other UI (feature cards) can ask for a screen: root.dispatchEvent(new CustomEvent('phone:goto', { detail: { index } })).
        this.root.addEventListener('phone:goto', (e) => this.show(e.detail.index));
        const el = this.root;
        const pointers = new Map();
        let start = null;
        let pinchStart = 0;

        const pinchDist = () => {
            const [a, b] = [...pointers.values()];
            return Math.hypot(a.x - b.x, a.y - b.y);
        };

        el.addEventListener('pointerdown', (e) => {
            if (!el.classList.contains('is-3d')) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 1) {
                start = { x: e.clientX, y: e.clientY, t: performance.now(), rx: this.target.x, ry: this.target.y };
            } else if (pointers.size === 2) {
                pinchStart = pinchDist() / this.targetZoom;
            }
            this.dragging = true;
            this.lastInteraction = performance.now();
            this.accent?.onInteract?.();
        });

        el.addEventListener('pointermove', (e) => {
            const rect = el.getBoundingClientRect();
            if (e.pointerType === 'mouse' && !this.dragging) {
                // Hover tilt toward the cursor
                // Clamped: on the hero the canvas (and so the cursor) reaches far past the phone.
                this.hover.y = clamp(((e.clientX - rect.left) / rect.width - 0.5) * 0.5, -0.3, 0.3);
                this.hover.x = clamp(((e.clientY - rect.top) / rect.height - 0.5) * 0.3, -0.2, 0.2);
                if (this.accent?.onHover) {
                    const clickable = this.accent.onHover(this.pick(e)?.object ?? null);
                    el.style.cursor = clickable ? 'pointer' : '';
                }
            }
            if (!pointers.has(e.pointerId) || !start) return;
            pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });
            if (pointers.size === 2) {
                this.targetZoom = clamp(pinchDist() / pinchStart, 0.75, 1.7);
                return;
            }
            const dx = e.clientX - start.x;
            const dy = e.clientY - start.y;
            if (Math.abs(dx) > 4 && e.pointerType === 'mouse' && !el.hasPointerCapture(e.pointerId)) {
                try {
                    el.setPointerCapture(e.pointerId);
                } catch {
                    /* pointer already released */
                }
            }
            this.target.y = start.ry + dx * 0.012;
            this.target.x = clamp(start.rx + dy * 0.006, -0.6, 0.6);
        });

        const end = (e) => {
            if (!pointers.has(e.pointerId)) return;
            pointers.delete(e.pointerId);
            if (pointers.size > 0 || !start) return;
            const dx = e.clientX - start.x;
            const dy = e.clientY - start.y;
            const dt = performance.now() - start.t;
            const dist = Math.hypot(dx, dy);
            const flick = dt < 380 && Math.abs(dx) > 45 && Math.abs(dx) > Math.abs(dy) * 1.4;
            if (e.type === 'pointerup' && flick && this.accent?.ownsDrag?.()) {
                // Browser in front: quick flick = next / previous web screen
                this.target.y = start.ry;
                this.accent.flick?.(dx < 0 ? 1 : -1);
            } else if (e.type === 'pointerup' && flick) {
                // Quick flick = change screen (undo the rotation it caused)
                this.target.y = start.ry;
                this.show(this.index + (dx < 0 ? 1 : -1));
            } else if (e.type === 'pointerup' && dist < 6 && dt < 400) {
                this.click(e);
            }
            start = null;
            this.dragging = false;
            this.lastInteraction = performance.now();
        };
        el.addEventListener('pointerup', end);
        el.addEventListener('pointercancel', end);
        el.addEventListener('pointerleave', (e) => {
            if (e.pointerType !== 'mouse') return;
            this.hover = { x: 0, y: 0 };
            this.accent?.onHover?.(null);
            el.style.cursor = '';
        });

        // Trackpad pinch (ctrl + wheel) zooms; a normal wheel keeps scrolling the page.
        el.addEventListener('wheel', (e) => {
            if (!e.ctrlKey) return;
            e.preventDefault();
            this.targetZoom = clamp(this.targetZoom * (e.deltaY < 0 ? 1.08 : 0.93), 0.75, 1.7);
        }, { passive: false });

        el.addEventListener('keydown', (e) => {
            const keys = { ArrowRight: 1, ArrowLeft: -1 };
            if (keys[e.key]) {
                e.preventDefault();
                this.show(this.index + keys[e.key]);
            }
            if ((e.key === 'Enter' || e.key === ' ') && this.accent?.toggle) {
                e.preventDefault();
                this.accent.toggle();
            }
            if (e.key === '+' || e.key === '=') this.targetZoom = clamp(this.targetZoom * 1.15, 0.75, 1.7);
            if (e.key === '-') this.targetZoom = clamp(this.targetZoom / 1.15, 0.75, 1.7);
            this.lastInteraction = performance.now();
        });

        // Gyroscope tilt where it is available without a permission prompt (Android).
        if (this.mobile && typeof DeviceOrientationEvent !== 'undefined' && typeof DeviceOrientationEvent.requestPermission !== 'function') {
            window.addEventListener('deviceorientation', (e) => {
                if (e.gamma == null) return;
                this.hover.y = clamp(e.gamma / 90, -0.35, 0.35);
                this.hover.x = clamp((e.beta - 45) / 180, -0.2, 0.2);
            });
        }

        this.ro = new ResizeObserver(() => this.resize());
        this.ro.observe(this.root);
        this.io = new IntersectionObserver(([entry]) => {
            this.visible = entry.isIntersecting;
            this.wake();
        });
        this.io.observe(this.root);
        document.addEventListener('visibilitychange', () => this.wake());
    }

    /** Restart the frame loop if it was paused (offscreen / hidden tab). */
    wake() {
        if (this.visible && !document.hidden && !this.running && !this.destroyed) {
            this.running = true;
            this.clock.getDelta();
            requestAnimationFrame(this.loop);
        }
    }

    /** Closest accent-scene clickable under the pointer, if any. */
    pick(e) {
        const targets = this.accent?.clickables;
        if (!targets?.length) return null;
        const rect = this.renderer.domElement.getBoundingClientRect();
        const ndc = new THREE.Vector2(((e.clientX - rect.left) / rect.width) * 2 - 1, -((e.clientY - rect.top) / rect.height) * 2 + 1);
        this.raycaster.setFromCamera(ndc, this.camera);
        return this.raycaster.intersectObjects(targets, true)[0] ?? null;
    }

    click(e) {
        const hit = this.pick(e);
        if (hit) this.accent.onClick?.(hit.object);
    }

    // ---------- Frame loop ----------
    loop() {
        if (this.destroyed || !this.visible || document.hidden) {
            this.running = false;
            return;
        }
        this.running = true;
        const dt = Math.min(this.clock.getDelta(), 0.05);
        const t = this.clock.elapsedTime;
        const idle = performance.now() - this.lastInteraction > 2500;

        // Ease back to the front after a drag; gentle idle sway unless reduced motion.
        if (!this.dragging) {
            this.target.x *= 0.95;
            this.target.y *= 0.95;
        }
        const sway = this.reduced ? 0 : Math.sin(t * 0.6) * 0.12;
        this.rot.x += (this.target.x + this.hover.x - this.rot.x) * 0.1;
        this.rot.y += (this.target.y + this.hover.y + sway - this.rot.y) * 0.1;
        this.pivot.rotation.set(this.rot.x, this.rot.y, 0);
        this.pivot.position.y = this.reduced ? 0 : Math.sin(t * 0.9) * 0.03;

        this.zoom += (this.targetZoom - this.zoom) * 0.12;
        this.camera.position.z = this.baseDistance / this.zoom;

        // Screen cross-fade
        if (this.fade) {
            const k = Math.min(1, (t - this.fade.start) / 0.35);
            this.fade.b.material.opacity = k;
            if (k >= 1) {
                this.fade.a.material.map = this.fade.tex;
                this.fade.a.material.needsUpdate = true;
                this.fade.b.material.opacity = 0;
                this.fade = null;
            }
        }

        // Hero: auto-advance through app screens while idle.
        if (this.isHero && !this.reduced && idle && t - (this.lastAuto ?? 0) > 3.2) {
            this.lastAuto = t;
            if (t > 3) this.show(this.index + 1);
        }

        if (this.placeholder && (!this.reduced || !this.drawnOnce)) {
            this.placeholder.draw(t);
            this.drawnOnce = true;
        }
        this.accent?.update?.(t, dt);

        this.renderer.render(this.scene, this.camera);
        requestAnimationFrame(this.loop);
    }

    destroy() {
        this.destroyed = true;
        this.ro?.disconnect();
        this.io?.disconnect();
        this.renderer.dispose();
        this.holder.remove();
        this.root.classList.remove('is-3d');
        this.root.removeAttribute('tabindex');
    }
}
