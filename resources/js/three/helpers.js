// Small shared helpers for the 3D scenes (shapes, materials, text textures).
import * as THREE from 'three';

export const std = (color, opts = {}) => new THREE.MeshStandardMaterial({ color, roughness: 0.45, metalness: 0.1, ...opts });

export function roundedRectShape(w, h, r) {
    const s = new THREE.Shape();
    const x = -w / 2;
    const y = -h / 2;
    s.moveTo(x + r, y);
    s.lineTo(x + w - r, y);
    s.quadraticCurveTo(x + w, y, x + w, y + r);
    s.lineTo(x + w, y + h - r);
    s.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
    s.lineTo(x + r, y + h);
    s.quadraticCurveTo(x, y + h, x, y + h - r);
    s.lineTo(x, y + r);
    s.quadraticCurveTo(x, y, x + r, y);
    return s;
}

/** Rounded box (extruded rounded rectangle), centered. */
export function roundedBox(w, h, d, r, material) {
    const geo = new THREE.ExtrudeGeometry(roundedRectShape(w - r, h - r, r), {
        depth: Math.max(0.001, d - r),
        bevelEnabled: true,
        bevelThickness: r / 2,
        bevelSize: r / 2,
        bevelSegments: 3,
        curveSegments: 8,
    });
    geo.center();
    return new THREE.Mesh(geo, material);
}

/** Flat shape geometry with UVs mapped to 0..1 (for textured screens and labels). */
export function shapeWithUv(shape, w, h) {
    const geo = new THREE.ShapeGeometry(shape, 12);
    const pos = geo.attributes.position;
    const uv = geo.attributes.uv;
    for (let i = 0; i < pos.count; i++) {
        uv.setXY(i, (pos.getX(i) + w / 2) / w, (pos.getY(i) + h / 2) / h);
    }
    return geo;
}

/** Heart mesh (point down), roughly `size` tall. */
export function heart(size, material, depth = 0.4) {
    const s = new THREE.Shape();
    s.moveTo(5, 5);
    s.bezierCurveTo(5, 5, 4, 0, 0, 0);
    s.bezierCurveTo(-6, 0, -6, 7, -6, 7);
    s.bezierCurveTo(-6, 11, -3, 15.4, 5, 19);
    s.bezierCurveTo(12, 15.4, 16, 11, 16, 7);
    s.bezierCurveTo(16, 7, 16, 0, 10, 0);
    s.bezierCurveTo(7, 0, 5, 5, 5, 5);
    const geo = new THREE.ExtrudeGeometry(s, { depth: 6 * depth, bevelEnabled: true, bevelThickness: 2, bevelSize: 1.5, bevelSegments: 4, curveSegments: 16 });
    geo.center();
    geo.rotateZ(Math.PI);
    geo.scale(size / 20, size / 20, size / 20);
    return new THREE.Mesh(geo, material);
}

/** Canvas texture with centered text. */
export function textTexture(text, { size = 256, aspect = 1, bg = null, fg = '#fff', font = 700, scale = 0.42, radius = 0.18 } = {}) {
    const c = document.createElement('canvas');
    c.width = size * aspect;
    c.height = size;
    const g = c.getContext('2d');
    if (bg) {
        g.fillStyle = bg;
        g.beginPath();
        g.roundRect(0, 0, c.width, c.height, size * radius);
        g.fill();
    }
    g.fillStyle = fg;
    g.font = `${font} ${size * scale}px "Plus Jakarta Sans", system-ui, sans-serif`;
    g.textAlign = 'center';
    g.textBaseline = 'middle';
    g.fillText(text, c.width / 2, c.height / 2 + size * 0.03);
    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;
    tex.anisotropy = 4;
    return tex;
}

/** Text label that always faces the camera. */
export function label(text, color, height = 0.14) {
    const tex = textTexture(text, { aspect: 3, bg: 'rgba(8,12,24,0.78)', fg: color, scale: 0.42, radius: 0.5 });
    const sprite = new THREE.Sprite(new THREE.SpriteMaterial({ map: tex, transparent: true, depthWrite: false }));
    sprite.scale.set(height * 3, height, 1);
    return sprite;
}

/** Chat bubble: rounded body + tail, with three "typing" dots. */
export function chatBubble(color, { w = 0.46, h = 0.26, tail = 'left', dots = '#ffffff' } = {}) {
    const group = new THREE.Group();
    const shape = roundedRectShape(w, h, h * 0.42);
    const sx = tail === 'left' ? -w / 2 + 0.06 : w / 2 - 0.06;
    const dir = tail === 'left' ? -1 : 1;
    const tailShape = new THREE.Shape();
    tailShape.moveTo(sx, -h / 2 + 0.02);
    tailShape.lineTo(sx + dir * 0.06, -h / 2 - 0.07);
    tailShape.lineTo(sx - dir * 0.08, -h / 2 + 0.02);
    const mat = std(color, { roughness: 0.35 });
    const opts = { depth: 0.04, bevelEnabled: true, bevelThickness: 0.015, bevelSize: 0.015, bevelSegments: 2, curveSegments: 10 };
    const body = new THREE.Mesh(new THREE.ExtrudeGeometry(shape, opts), mat);
    const tailMesh = new THREE.Mesh(new THREE.ExtrudeGeometry(tailShape, opts), mat);
    group.add(body, tailMesh);
    const dotMat = std(dots, { roughness: 0.3 });
    const dotGeo = new THREE.SphereGeometry(h * 0.1, 12, 8);
    group.userData.dots = [-1, 0, 1].map((i) => {
        const d = new THREE.Mesh(dotGeo, dotMat);
        d.position.set(i * h * 0.32, 0, 0.07);
        group.add(d);
        return d;
    });
    return group;
}

/** Gentle up/down float. */
export function float(obj, t, { base = 0, amp = 0.05, speed = 1, phase = 0 } = {}) {
    obj.position.y = base + Math.sin(t * speed + phase) * amp;
}

export const ease = {
    outBack: (x) => 1 + 2.70158 * Math.pow(x - 1, 3) + 1.70158 * Math.pow(x - 1, 2),
    inOut: (x) => (x < 0.5 ? 2 * x * x : 1 - Math.pow(-2 * x + 2, 2) / 2),
};

export function disposeTree(root) {
    root.traverse((o) => {
        o.geometry?.dispose?.();
        const mats = Array.isArray(o.material) ? o.material : o.material ? [o.material] : [];
        mats.forEach((m) => {
            m.map?.dispose?.();
            m.dispose?.();
        });
    });
}
