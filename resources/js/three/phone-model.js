// Phone built from primitives: rounded body, screen with real UVs, island, buttons and camera bump.
import * as THREE from 'three';
import { roundedRectShape, roundedBox, shapeWithUv, std } from './helpers';

export const PHONE = { width: 1, height: 2.16, depth: 0.09 };

export function createPhone() {
    const { width: W, height: H, depth: D } = PHONE;
    const group = new THREE.Group();

    // Body
    const bevel = 0.025;
    const bodyGeo = new THREE.ExtrudeGeometry(roundedRectShape(W - bevel * 2, H - bevel * 2, 0.15), {
        depth: D,
        bevelEnabled: true,
        bevelThickness: bevel,
        bevelSize: bevel,
        bevelSegments: 5,
        curveSegments: 24,
    });
    bodyGeo.center();
    const bodyMat = std('#1a1f2e', { metalness: 0.75, roughness: 0.3 });
    group.add(new THREE.Mesh(bodyGeo, bodyMat));
    const front = D / 2 + bevel;

    // Black glass
    const glassW = W - 0.02;
    const glassH = H - 0.02;
    const glass = new THREE.Mesh(new THREE.ShapeGeometry(roundedRectShape(glassW, glassH, 0.15), 24), new THREE.MeshStandardMaterial({ color: '#05070d', roughness: 0.15, metalness: 0.2 }));
    glass.position.z = front + 0.001;
    group.add(glass);

    // Screen (two stacked layers for cross-fading between screenshots)
    const sw = W - 0.08;
    const sh = H - 0.08;
    const screenGeo = shapeWithUv(roundedRectShape(sw, sh, 0.12), sw, sh);
    const makeScreen = (z) => {
        const m = new THREE.Mesh(screenGeo, new THREE.MeshBasicMaterial({ color: '#ffffff', toneMapped: false, transparent: true, opacity: 1 }));
        m.position.z = z;
        group.add(m);
        return m;
    };
    const screenA = makeScreen(front + 0.002);
    const screenB = makeScreen(front + 0.003);
    screenB.material.opacity = 0;

    // Dynamic island
    const island = new THREE.Mesh(new THREE.ShapeGeometry(roundedRectShape(0.26, 0.065, 0.032), 12), new THREE.MeshBasicMaterial({ color: '#000' }));
    island.position.set(0, sh / 2 - 0.07, front + 0.004);
    group.add(island);

    // Side buttons
    const btnMat = std('#2a3042', { metalness: 0.8, roughness: 0.3 });
    [[W / 2 + 0.012, 0.45, 0.22], [-W / 2 - 0.012, 0.55, 0.12], [-W / 2 - 0.012, 0.32, 0.18]].forEach(([x, y, h]) => {
        const b = roundedBox(0.025, h, 0.05, 0.012, btnMat);
        b.position.set(x, y, 0);
        group.add(b);
    });

    // Camera bump on the back
    const bump = roundedBox(0.36, 0.36, 0.04, 0.08, std('#202638', { metalness: 0.6, roughness: 0.35 }));
    bump.position.set(-W / 2 + 0.26, H / 2 - 0.26, -front - 0.015);
    group.add(bump);
    const lensMat = new THREE.MeshStandardMaterial({ color: '#0b0d14', metalness: 0.9, roughness: 0.1 });
    [[-0.07, 0.07], [0.07, -0.07], [-0.07, -0.07]].forEach(([x, y]) => {
        const lens = new THREE.Mesh(new THREE.CylinderGeometry(0.055, 0.055, 0.03, 24), lensMat);
        lens.rotation.x = Math.PI / 2;
        lens.position.set(bump.position.x + x, bump.position.y + y, bump.position.z - 0.025);
        group.add(lens);
    });

    return { group, screenA, screenB, screenAspect: sw / sh };
}
