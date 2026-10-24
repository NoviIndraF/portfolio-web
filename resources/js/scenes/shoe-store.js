// Shamo: a sneaker turning on a pedestal (or the uploaded .glb), with chat bubbles popping up.
import * as THREE from 'three';
import { std, chatBubble, float } from '../three/helpers';

function sneaker(accentHex) {
    const g = new THREE.Group();
    const extrude = (shape, depth, mat) => {
        const geo = new THREE.ExtrudeGeometry(shape, { depth, bevelEnabled: true, bevelThickness: 0.03, bevelSize: 0.03, bevelSegments: 4, curveSegments: 16 });
        geo.translate(0, 0, -depth / 2);
        return new THREE.Mesh(geo, mat);
    };
    // Side profile of the sole and upper (x = length, y = height)
    const sole = new THREE.Shape();
    sole.moveTo(-0.5, 0);
    sole.lineTo(0.42, 0);
    sole.quadraticCurveTo(0.56, 0.02, 0.54, 0.1);
    sole.lineTo(-0.5, 0.1);
    sole.quadraticCurveTo(-0.56, 0.05, -0.5, 0);
    const upper = new THREE.Shape();
    upper.moveTo(-0.48, 0.1);
    upper.lineTo(-0.5, 0.34);
    upper.quadraticCurveTo(-0.42, 0.44, -0.28, 0.42);
    upper.lineTo(-0.14, 0.36);
    upper.quadraticCurveTo(0.08, 0.33, 0.24, 0.24);
    upper.quadraticCurveTo(0.48, 0.2, 0.52, 0.1);
    upper.lineTo(-0.48, 0.1);
    g.add(extrude(sole, 0.3, std('#f4f5f7', { roughness: 0.7 })));
    g.add(extrude(upper, 0.26, std(accentHex, { roughness: 0.55 })));
    // Stripe and laces
    const stripe = new THREE.Mesh(new THREE.TorusGeometry(0.16, 0.018, 8, 24, Math.PI * 0.9), std('#ffffff'));
    stripe.position.set(0.02, 0.2, 0.16);
    stripe.rotation.z = -0.4;
    g.add(stripe);
    for (let i = 0; i < 4; i++) {
        const lace = new THREE.Mesh(new THREE.BoxGeometry(0.03, 0.012, 0.24), std('#ffffff'));
        lace.position.set(-0.1 + i * 0.075, 0.37 - i * 0.03, 0);
        lace.rotation.z = -0.35;
        g.add(lace);
    }
    g.position.y = -0.2;
    const wrap = new THREE.Group();
    wrap.add(g);
    return wrap;
}

export default function create({ group, accentHex, model }) {
    const shoe = model ?? sneaker(accentHex);
    const pedestal = new THREE.Mesh(new THREE.CylinderGeometry(0.42, 0.46, 0.08, 48), std('#1d2335', { metalness: 0.5, roughness: 0.4 }));
    const ring = new THREE.Mesh(new THREE.TorusGeometry(0.44, 0.012, 8, 64), new THREE.MeshBasicMaterial({ color: accentHex }));
    ring.rotation.x = Math.PI / 2;
    ring.position.y = 0.045;
    pedestal.add(ring);
    const stand = new THREE.Group();
    shoe.position.y = 0.32;
    stand.add(pedestal, shoe);
    stand.scale.setScalar(0.85);

    const bubbles = [chatBubble('#ffffff', { tail: 'left', dots: accentHex }), chatBubble(accentHex, { tail: 'right' })];
    group.add(stand, ...bubbles);

    return {
        layout({ spread, front }) {
            stand.position.set(-spread * 0.72, -0.85, front);
            bubbles[0].position.set(spread * 0.78, 0.55, front);
            bubbles[1].position.set(spread * 0.7, 0.05, front - 0.1);
            this.base = bubbles.map((b) => b.position.y);
        },
        update(t) {
            shoe.rotation.y = t * 0.8;
            stand.rotation.x = 0.15;
            if (!this.base) return;
            bubbles.forEach((b, i) => {
                // Pop in and out one after another
                const cycle = (t * 0.5 + i * 0.5) % 1;
                const s = cycle < 0.7 ? Math.min(1, cycle * 6) : Math.max(0, 1 - (cycle - 0.7) * 5);
                b.scale.setScalar(0.001 + s);
                float(b, t, { base: this.base[i], amp: 0.03, speed: 1.4, phase: i });
                b.userData.dots.forEach((d, n) => (d.position.y = Math.max(0, Math.sin(t * 6 - n * 0.8)) * 0.025));
            });
        },
    };
}
