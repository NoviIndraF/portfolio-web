// Hero background: soft particles that drift and move away from the cursor / finger.
import * as THREE from 'three';

export default function mountHeroParticles(el, { reduced = false, mobile = false, color = '#4c8dff' } = {}) {
    const section = el.closest('section') ?? el;
    const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: false, powerPreference: 'low-power' });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 1.5));
    el.appendChild(renderer.domElement);
    renderer.domElement.addEventListener('webglcontextlost', (e) => {
        e.preventDefault();
        visible = false;
        renderer.domElement.remove();
    });
    renderer.domElement.style.cssText = 'width:100%;height:100%;display:block;opacity:0;transition:opacity 1s';

    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(50, 1, 0.1, 50);
    camera.position.z = 8;

    const N = mobile ? 260 : 700;
    const home = new Float32Array(N * 3);
    const pos = new Float32Array(N * 3);
    const geo = new THREE.BufferGeometry();
    geo.setAttribute('position', new THREE.BufferAttribute(pos, 3));

    // Round soft sprite
    const c = document.createElement('canvas');
    c.width = c.height = 64;
    const g = c.getContext('2d');
    const grd = g.createRadialGradient(32, 32, 0, 32, 32, 32);
    grd.addColorStop(0, 'rgba(255,255,255,1)');
    grd.addColorStop(0.4, 'rgba(255,255,255,0.5)');
    grd.addColorStop(1, 'rgba(255,255,255,0)');
    g.fillStyle = grd;
    g.fillRect(0, 0, 64, 64);
    const sprite = new THREE.CanvasTexture(c);

    const mat = new THREE.PointsMaterial({ color, size: 0.09, map: sprite, transparent: true, depthWrite: false, blending: THREE.AdditiveBlending, opacity: 0.8 });
    scene.add(new THREE.Points(geo, mat));

    let halfW = 1;
    let halfH = 1;
    const spawn = () => {
        for (let i = 0; i < N; i++) {
            home[i * 3] = (Math.random() * 2 - 1) * halfW * 1.1;
            home[i * 3 + 1] = (Math.random() * 2 - 1) * halfH * 1.1;
            home[i * 3 + 2] = Math.random() * -4 + 1;
        }
        pos.set(home);
        geo.attributes.position.needsUpdate = true;
    };

    const resize = () => {
        const { width, height } = el.getBoundingClientRect();
        if (!width) return;
        renderer.setSize(width, height, false);
        camera.aspect = width / height;
        camera.updateProjectionMatrix();
        halfH = Math.tan(THREE.MathUtils.degToRad(25)) * camera.position.z;
        halfW = halfH * camera.aspect;
        spawn();
    };

    // Pointer in world units on the z=0 plane
    const pointer = new THREE.Vector2(9999, 9999);
    const setPointer = (x, y) => {
        const r = el.getBoundingClientRect();
        pointer.set(((x - r.left) / r.width * 2 - 1) * halfW, -((y - r.top) / r.height * 2 - 1) * halfH);
    };
    section.addEventListener('pointermove', (e) => setPointer(e.clientX, e.clientY), { passive: true });
    section.addEventListener('touchmove', (e) => setPointer(e.touches[0].clientX, e.touches[0].clientY), { passive: true });
    section.addEventListener('pointerleave', () => pointer.set(9999, 9999));

    let visible = true;
    let running = false;
    const clock = new THREE.Clock();
    const R = 1.6;

    const frame = () => {
        if (!visible || document.hidden) {
            running = false;
            return;
        }
        const t = clock.getElapsedTime();
        for (let i = 0; i < N; i++) {
            const k = i * 3;
            let tx = home[k] + (reduced ? 0 : Math.sin(t * 0.3 + i) * 0.15);
            let ty = home[k + 1] + (reduced ? 0 : Math.cos(t * 0.25 + i * 0.7) * 0.15);
            const dx = pos[k] - pointer.x;
            const dy = pos[k + 1] - pointer.y;
            const d = Math.hypot(dx, dy);
            if (d < R && d > 0.0001) {
                const push = ((R - d) / R) * 1.2;
                tx += (dx / d) * push;
                ty += (dy / d) * push;
            }
            pos[k] += (tx - pos[k]) * 0.06;
            pos[k + 1] += (ty - pos[k + 1]) * 0.06;
        }
        geo.attributes.position.needsUpdate = true;
        renderer.render(scene, camera);
        requestAnimationFrame(frame);
    };
    const wake = () => {
        if (visible && !document.hidden && !running) {
            running = true;
            requestAnimationFrame(frame);
        }
    };

    new ResizeObserver(resize).observe(el);
    new IntersectionObserver(([e]) => {
        visible = e.isIntersecting;
        wake();
    }).observe(el);
    document.addEventListener('visibilitychange', wake);
    resize();
    wake();
    requestAnimationFrame(() => (renderer.domElement.style.opacity = '1'));
}
