// Generic scene for any project: soft floating shapes in the project's accent color.
import * as THREE from 'three';
import { std, float } from '../three/helpers';

export default function create({ group, accent }) {
    const light = accent.clone().offsetHSL(0, 0, 0.18);
    const shapes = [
        new THREE.Mesh(new THREE.TorusKnotGeometry(0.16, 0.05, 96, 12), std(accent, { metalness: 0.3, roughness: 0.3 })),
        new THREE.Mesh(new THREE.IcosahedronGeometry(0.2, 0), std(light, { flatShading: true, roughness: 0.4 })),
        new THREE.Mesh(new THREE.TorusGeometry(0.16, 0.05, 16, 48), std('#ffffff', { roughness: 0.3 })),
        new THREE.Mesh(new THREE.SphereGeometry(0.12, 32, 16), std(accent, { metalness: 0.6, roughness: 0.2 })),
        new THREE.Mesh(new THREE.OctahedronGeometry(0.15, 0), std(light, { flatShading: true })),
    ];
    group.add(...shapes);
    const spots = [[-0.82, 0.6, 0], [0.8, 0.5, -0.2], [-0.75, -0.55, 0.1], [0.78, -0.6, 0], [-0.9, 0.05, -0.5]];

    return {
        layout({ spread, front }) {
            shapes.forEach((s, i) => {
                const [x, y, z] = spots[i];
                s.position.set(x * spread, y, z + front);
                s.userData.base = y;
            });
        },
        update(t) {
            shapes.forEach((s, i) => {
                if (s.userData.base === undefined) return;
                float(s, t, { base: s.userData.base, amp: 0.05, speed: 0.8 + i * 0.15, phase: i });
                s.rotation.x = t * (0.3 + i * 0.07);
                s.rotation.y = t * (0.4 + i * 0.05);
            });
        },
    };
}
