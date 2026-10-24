// Chat Apps: connection nodes around the phone; a message pulse travels and the receiving node lights up.
import * as THREE from 'three';
import { std, chatBubble } from '../three/helpers';

export default function create({ group, accent, accentHex }) {
    const nodeGeo = new THREE.SphereGeometry(0.075, 24, 16);
    const nodes = Array.from({ length: 6 }, (_, i) => {
        const m = new THREE.Mesh(nodeGeo, std(i % 2 ? '#ffffff' : accentHex, { emissive: accent, emissiveIntensity: 0.15 }));
        group.add(m);
        return m;
    });
    const hub = new THREE.Vector3(0, 0, -0.3);
    const lineGeo = new THREE.BufferGeometry();
    const lines = new THREE.LineSegments(lineGeo, new THREE.LineBasicMaterial({ color: accentHex, transparent: true, opacity: 0.55, toneMapped: false }));
    group.add(lines);

    const pulse = new THREE.Mesh(new THREE.SphereGeometry(0.04, 12, 8), new THREE.MeshBasicMaterial({ color: '#ffffff' }));
    group.add(pulse);
    const bubbles = [chatBubble(accentHex, { tail: 'left' }), chatBubble('#ffffff', { tail: 'right', dots: accentHex })];
    bubbles.forEach((b) => {
        b.scale.setScalar(0.001);
        group.add(b);
    });

    let route = null;
    let nextAt = 0;
    let bubbleI = 0;

    return {
        layout({ spread, front }) {
            const pts = [
                [-0.85, 0.7, front - 0.2], [0.85, 0.75, -0.4], [-0.95, -0.05, -0.5],
                [0.9, -0.1, front], [-0.75, -0.8, front - 0.1], [0.78, -0.82, -0.3],
            ];
            nodes.forEach((n, i) => n.position.set(pts[i][0] * spread, pts[i][1], pts[i][2]));
            const arr = [];
            nodes.forEach((n, i) => {
                arr.push(...hub.toArray(), ...n.position.toArray());
                const m = nodes[(i + 1) % nodes.length];
                if (i % 2 === 0) arr.push(...n.position.toArray(), ...m.position.toArray());
            });
            lineGeo.setAttribute('position', new THREE.Float32BufferAttribute(arr, 3));
            route = null;
        },
        update(t) {
            nodes.forEach((n) => n.scale.lerp(new THREE.Vector3(1, 1, 1), 0.08));
            if (!route && t > nextAt) {
                const a = Math.floor(Math.random() * nodes.length);
                let b = (a + 1 + Math.floor(Math.random() * (nodes.length - 1))) % nodes.length;
                route = { a: nodes[a], b: nodes[b], start: t };
            }
            if (route) {
                const k = Math.min(1, (t - route.start) / 0.7);
                // Travel via the hub, like a message through the server
                const p = k < 0.5 ? route.a.position.clone().lerp(hub, k * 2) : hub.clone().lerp(route.b.position, (k - 0.5) * 2);
                pulse.position.copy(p);
                pulse.visible = true;
                if (k >= 1) {
                    route.b.scale.setScalar(1.8);
                    const bubble = bubbles[bubbleI++ % bubbles.length];
                    bubble.position.copy(route.b.position).add(new THREE.Vector3(0, 0.22, 0.05));
                    bubble.userData.born = t;
                    route = null;
                    nextAt = t + 0.9;
                    pulse.visible = false;
                }
            }
            bubbles.forEach((b) => {
                if (b.userData.born == null) return;
                const age = t - b.userData.born;
                const s = age < 0.25 ? age / 0.25 : age > 1.4 ? Math.max(0.001, 1 - (age - 1.4) * 3) : 1;
                b.scale.setScalar(Math.max(0.001, s * 0.8));
                b.position.y += 0.002;
                b.userData.dots.forEach((d, n) => (d.position.y = Math.max(0, Math.sin(t * 6 - n * 0.8)) * 0.025));
            });
        },
    };
}
