// Donice: map pins for donor, merchant and recipient; coins and hearts flow between them.
import * as THREE from 'three';
import { std, heart, label } from '../three/helpers';

export default function create({ group, accentHex }) {
    const pin = (color, text) => {
        const g = new THREE.Group();
        const mat = std(color, { roughness: 0.3, metalness: 0.2 });
        const head = new THREE.Mesh(new THREE.SphereGeometry(0.11, 24, 16), mat);
        head.position.y = 0.16;
        const tip = new THREE.Mesh(new THREE.ConeGeometry(0.075, 0.2, 24), mat);
        tip.rotation.x = Math.PI;
        tip.position.y = 0.03;
        const dot = new THREE.Mesh(new THREE.SphereGeometry(0.045, 16, 12), std('#ffffff'));
        dot.position.set(0, 0.16, 0.08);
        const tag = label(text, '#ffffff', 0.11);
        tag.position.y = 0.38;
        g.add(head, tip, dot, tag);
        return g;
    };
    const donor = pin(accentHex, 'Donor');
    const merchant = pin('#f4b740', 'Merchant');
    const recipient = pin('#2fbf71', 'Recipient');
    group.add(donor, merchant, recipient);

    const coinGeo = new THREE.CylinderGeometry(0.06, 0.06, 0.018, 24);
    const coinMat = std('#f6c445', { metalness: 0.85, roughness: 0.25 });
    const heartMat = std('#e3173b', { roughness: 0.35, emissive: '#4a0010', emissiveIntensity: 0.5 });

    // Travelers on three routes: donor → merchant (coins), merchant → recipient (hearts), donor → recipient (hearts)
    const routes = [
        { from: donor, to: merchant, make: () => new THREE.Mesh(coinGeo, coinMat), count: 4, speed: 0.22 },
        { from: merchant, to: recipient, make: () => heart(0.11, heartMat), count: 3, speed: 0.18 },
        { from: donor, to: recipient, make: () => heart(0.09, heartMat), count: 3, speed: 0.2 },
    ];
    routes.forEach((r) => {
        r.items = Array.from({ length: r.count }, (_, i) => {
            const m = r.make();
            m.userData.offset = i / r.count;
            group.add(m);
            return m;
        });
    });

    const curve = (a, b) => {
        const p0 = a.position.clone().setY(a.position.y + 0.12);
        const p2 = b.position.clone().setY(b.position.y + 0.12);
        const mid = p0.clone().lerp(p2, 0.5);
        mid.z += 0.9;
        mid.y += 0.35;
        return new THREE.QuadraticBezierCurve3(p0, mid, p2);
    };

    return {
        layout({ spread, front }) {
            donor.position.set(-spread * 0.82, 0.55, front - 0.2);
            merchant.position.set(spread * 0.82, 0.25, front - 0.2);
            recipient.position.set(-spread * 0.7, -0.75, front);
            routes.forEach((r) => (r.curve = curve(r.from, r.to)));
        },
        update(t) {
            [donor, merchant, recipient].forEach((p, i) => (p.children[0].position.y = 0.16 + Math.sin(t * 2 + i) * 0.015));
            routes.forEach((r) => {
                if (!r.curve) return;
                r.items.forEach((m) => {
                    const u = (t * r.speed + m.userData.offset) % 1;
                    m.position.copy(r.curve.getPoint(u));
                    const s = Math.sin(Math.PI * u);
                    m.scale.setScalar(0.4 + s * 0.8);
                    m.rotation.y = t * 3 + m.userData.offset * 6;
                    m.rotation.x = Math.PI / 2 * (m.geometry === coinGeo ? 1 : 0);
                });
            });
        },
    };
}
