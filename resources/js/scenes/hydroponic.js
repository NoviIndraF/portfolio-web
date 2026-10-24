// IO-Tani: plants growing in hydroponic pipes, falling water drops and blinking sensor lights.
import * as THREE from 'three';
import { std } from '../three/helpers';

export default function create({ group, accentHex }) {
    const pipeMat = std('#e9eef2', { roughness: 0.35 });
    const leafMats = ['#3fbf6a', '#2e9e5b', '#6fd38a'].map((c) => std(c, { roughness: 0.6, side: THREE.DoubleSide }));
    const stemMat = std('#2f8a4c');
    const cupMat = std('#1f2430');

    const pipes = [0, 1].map(() => {
        const g = new THREE.Group();
        const pipe = new THREE.Mesh(new THREE.CylinderGeometry(0.09, 0.09, 1, 24), pipeMat);
        pipe.rotation.z = Math.PI / 2;
        g.add(pipe);
        g.userData.pipe = pipe;
        g.userData.plants = [];
        group.add(g);
        return g;
    });

    const plant = (scale) => {
        const p = new THREE.Group();
        const cup = new THREE.Mesh(new THREE.CylinderGeometry(0.05, 0.04, 0.05, 16), cupMat);
        cup.position.y = 0.09;
        const stem = new THREE.Mesh(new THREE.CylinderGeometry(0.008, 0.01, 0.18, 6), stemMat);
        stem.position.y = 0.19;
        p.add(cup, stem);
        for (let i = 0; i < 5; i++) {
            const leaf = new THREE.Mesh(new THREE.SphereGeometry(0.06, 12, 8), leafMats[i % 3]);
            leaf.scale.set(1, 0.25, 0.55);
            const a = (i / 5) * Math.PI * 2;
            leaf.position.set(Math.cos(a) * 0.05, 0.25 + (i % 2) * 0.04, Math.sin(a) * 0.05);
            leaf.rotation.set(0.3, -a, 0.5);
            p.add(leaf);
        }
        p.scale.setScalar(scale);
        return p;
    };

    // Water drops
    const dropMat = new THREE.MeshStandardMaterial({ color: '#7cc8ff', transparent: true, opacity: 0.85, roughness: 0.1, metalness: 0.1 });
    const drops = Array.from({ length: 10 }, (_, i) => {
        const d = new THREE.Mesh(new THREE.SphereGeometry(0.022, 10, 8), dropMat);
        d.scale.y = 1.4;
        d.userData.offset = i / 10;
        group.add(d);
        return d;
    });

    // Sensor box with blinking LEDs
    const sensor = new THREE.Group();
    sensor.add(new THREE.Mesh(new THREE.BoxGeometry(0.22, 0.14, 0.08), std('#273047', { roughness: 0.5 })));
    const probe = new THREE.Mesh(new THREE.CylinderGeometry(0.01, 0.01, 0.22, 8), std('#c0c6d4', { metalness: 0.9 }));
    probe.position.y = -0.17;
    sensor.add(probe);
    const leds = [accentHex, '#4dffb0', '#ffb547'].map((c, i) => {
        const l = new THREE.Mesh(new THREE.SphereGeometry(0.016, 10, 8), new THREE.MeshBasicMaterial({ color: c }));
        l.position.set(-0.06 + i * 0.06, 0.02, 0.045);
        sensor.add(l);
        return l;
    });
    group.add(sensor);

    let spread = 1;

    return {
        layout(l) {
            spread = l.spread;
            const length = spread * 2;
            pipes.forEach((g, i) => {
                g.position.set(0, i === 0 ? 0.35 : -0.55, -0.45);
                g.userData.pipe.scale.y = length;
                g.userData.plants.forEach((p) => g.remove(p));
                g.userData.plants = [];
                const count = Math.max(4, Math.round(length / 0.32));
                for (let k = 0; k < count; k++) {
                    const x = -length / 2 + 0.15 + (k * (length - 0.3)) / (count - 1);
                    if (Math.abs(x) < 0.45) continue; // behind the phone: skip
                    const p = plant(0.9 + ((k * 7) % 5) * 0.08);
                    p.position.x = x;
                    p.userData.phase = k;
                    g.add(p);
                    g.userData.plants.push(p);
                }
            });
            sensor.position.set(spread * 0.78, -0.05, l.front);
            drops.forEach((d, i) => (d.userData.x = (i % 2 ? 1 : -1) * (0.55 + ((i * 0.37) % 1) * (spread - 0.6))));
        },
        update(t) {
            pipes.forEach((g) => g.userData.plants.forEach((p) => (p.rotation.z = Math.sin(t * 1.2 + p.userData.phase) * 0.06)));
            drops.forEach((d) => {
                const u = (t * 0.6 + d.userData.offset) % 1;
                d.position.set(d.userData.x ?? 0, 0.28 - u * 0.75, -0.4);
                d.material.opacity = u > 0.85 ? (1 - u) * 6 : 0.85;
            });
            leds.forEach((l, i) => (l.visible = Math.sin(t * 3 + i * 2) > -0.2));
            sensor.position.y = -0.05 + Math.sin(t) * 0.03;
            sensor.rotation.y = Math.sin(t * 0.5) * 0.4 - 0.3;
        },
    };
}
