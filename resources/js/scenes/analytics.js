// Sidedi: neutral abstract data visuals: a 3D bar chart and a rotating radar chart.
import * as THREE from 'three';
import { std } from '../three/helpers';

export default function create({ group, accent, accentHex }) {
    // Bar chart on a base plate
    const chart = new THREE.Group();
    const plate = new THREE.Mesh(new THREE.BoxGeometry(0.8, 0.03, 0.5), std('#1d2335', { roughness: 0.5 }));
    chart.add(plate);
    const bars = [];
    for (let r = 0; r < 2; r++) {
        for (let c = 0; c < 5; c++) {
            const color = accent.clone().offsetHSL(0, 0, (c - 2) * 0.06 - r * 0.08);
            const bar = new THREE.Mesh(new THREE.BoxGeometry(0.1, 1, 0.1), std(color, { roughness: 0.35 }));
            bar.position.set(-0.3 + c * 0.15, 0, -0.1 + r * 0.2);
            bar.userData.seed = r * 5 + c;
            chart.add(bar);
            bars.push(bar);
        }
    }
    chart.rotation.set(0.35, -0.6, 0);

    // Radar: grid rings + a filled shape whose points move
    const radar = new THREE.Group();
    const sides = 6;
    const ring = (r) => {
        const pts = Array.from({ length: sides }, (_, i) => {
            const a = (i / sides) * Math.PI * 2 + Math.PI / 2;
            return new THREE.Vector3(Math.cos(a) * r, Math.sin(a) * r, 0);
        });
        return new THREE.LineLoop(new THREE.BufferGeometry().setFromPoints(pts), new THREE.LineBasicMaterial({ color: '#ffffff', transparent: true, opacity: 0.25 }));
    };
    [0.12, 0.22, 0.32].forEach((r) => radar.add(ring(r)));
    const fanPos = new Float32Array((sides + 1) * 3);
    const fanGeo = new THREE.BufferGeometry();
    fanGeo.setAttribute('position', new THREE.BufferAttribute(fanPos, 3));
    fanGeo.setIndex(Array.from({ length: sides }, (_, i) => [0, i + 1, ((i + 1) % sides) + 1]).flat());
    const fill = new THREE.Mesh(fanGeo, new THREE.MeshBasicMaterial({ color: accentHex, transparent: true, opacity: 0.45, side: THREE.DoubleSide }));
    const outlineGeo = new THREE.BufferGeometry();
    outlineGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(sides * 3), 3));
    const outline = new THREE.LineLoop(outlineGeo, new THREE.LineBasicMaterial({ color: accentHex, toneMapped: false }));
    radar.add(fill, outline);

    group.add(chart, radar);

    return {
        layout({ spread, front }) {
            chart.position.set(spread * 0.75, -0.75, front);
            radar.position.set(-spread * 0.78, 0.5, front);
        },
        update(t) {
            bars.forEach((b) => {
                const h = 0.15 + 0.35 * (0.5 + 0.5 * Math.sin(t * 0.9 + b.userData.seed * 1.7));
                b.scale.y = h;
                b.position.y = h / 2 + 0.015;
            });
            const op = outlineGeo.attributes.position;
            for (let i = 0; i < sides; i++) {
                const a = (i / sides) * Math.PI * 2 + Math.PI / 2;
                const r = 0.14 + 0.16 * (0.5 + 0.5 * Math.sin(t * 0.8 + i * 1.3));
                fanPos.set([Math.cos(a) * r, Math.sin(a) * r, 0.001], (i + 1) * 3);
                op.setXYZ(i, Math.cos(a) * r, Math.sin(a) * r, 0.002);
            }
            fanGeo.attributes.position.needsUpdate = true;
            op.needsUpdate = true;
            radar.rotation.y = Math.sin(t * 0.5) * 0.5;
            radar.rotation.z = t * 0.1;
            chart.rotation.y = -0.6 + Math.sin(t * 0.3) * 0.2;
        },
    };
}
