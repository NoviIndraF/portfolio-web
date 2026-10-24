// Codelytic: floating books, code blocks and a quiz cube you can click.
import * as THREE from 'three';
import { std, roundedBox, textTexture, float } from '../three/helpers';

export default function create({ group, accentHex }) {
    const book = (color) => {
        const g = new THREE.Group();
        g.add(roundedBox(0.5, 0.075, 0.36, 0.02, std(color, { roughness: 0.6 })));
        const pages = new THREE.Mesh(new THREE.BoxGeometry(0.46, 0.055, 0.33), std('#f3efe6', { roughness: 0.9 }));
        pages.position.x = 0.015;
        g.add(pages);
        return g;
    };
    const stack = new THREE.Group();
    [accentHex, '#f4b740', '#e8553d'].forEach((c, i) => {
        const b = book(c);
        b.position.y = i * 0.085;
        b.rotation.y = [0, 0.3, -0.18][i];
        stack.add(b);
    });
    stack.rotation.set(0.35, 0.5, 0.1);

    const codeBlock = (text, color) => {
        const g = new THREE.Group();
        g.add(roundedBox(0.52, 0.3, 0.06, 0.06, std(color, { roughness: 0.4 })));
        const face = new THREE.Mesh(new THREE.PlaneGeometry(0.44, 0.22), new THREE.MeshBasicMaterial({ map: textTexture(text, { aspect: 2, fg: '#ffffff', scale: 0.5 }), transparent: true }));
        face.position.z = 0.04;
        g.add(face);
        return g;
    };
    const code1 = codeBlock('</>', accentHex);
    const code2 = codeBlock('{ }', '#1f2a44');

    // Quiz cube: each face shows a letter; clicking spins it and shows a check mark.
    const faces = ['A', 'B', 'C', 'D', '?', '?'].map((l) => new THREE.MeshStandardMaterial({ map: textTexture(l, { bg: '#ffffff', fg: accentHex, scale: 0.55 }), roughness: 0.4 }));
    const check = textTexture('✓', { bg: '#2fbf71', fg: '#ffffff', scale: 0.6 });
    const cube = new THREE.Mesh(new THREE.BoxGeometry(0.4, 0.4, 0.4), faces);
    const cubeTarget = new THREE.Euler(0.4, 0.6, 0);
    let flashUntil = 0;
    let clock = 0;

    group.add(stack, code1, code2, cube);

    return {
        clickables: [cube],
        onClick() {
            cubeTarget.x += Math.PI / 2;
            cubeTarget.y += Math.PI / 2;
            // Show the check mark on every face so it is visible whichever side faces the camera.
            faces.forEach((f) => {
                f.userData.map ??= f.map;
                f.map = check;
                f.needsUpdate = true;
            });
            flashUntil = clock + 1.2;
        },
        layout({ spread, front }) {
            stack.position.set(-spread * 0.82, -0.62, front);
            code1.position.set(spread * 0.8, 0.62, front - 0.2);
            code2.position.set(-spread * 0.78, 0.68, -0.5);
            cube.position.set(spread * 0.8, -0.35, front + 0.1);
            this.base = { stack: stack.position.y, c1: code1.position.y, c2: code2.position.y, cube: cube.position.y };
        },
        update(t) {
            clock = t;
            if (!this.base) return;
            float(stack, t, { base: this.base.stack, amp: 0.04, speed: 0.8 });
            float(code1, t, { base: this.base.c1, amp: 0.05, speed: 1.1, phase: 1 });
            float(code2, t, { base: this.base.c2, amp: 0.05, speed: 0.9, phase: 2 });
            float(cube, t, { base: this.base.cube, amp: 0.05, speed: 1.3, phase: 3 });
            code1.rotation.y = Math.sin(t * 0.5) * 0.3 - 0.3;
            code2.rotation.y = Math.sin(t * 0.4 + 1) * 0.3 + 0.3;
            cube.rotation.x += (cubeTarget.x - cube.rotation.x) * 0.1;
            cube.rotation.y += (cubeTarget.y + t * 0.2 - cube.rotation.y) * 0.1;
            if (flashUntil && t > flashUntil) {
                faces.forEach((f) => {
                    f.map = f.userData.map;
                    f.needsUpdate = true;
                });
                flashUntil = 0;
            }
        },
    };
}
