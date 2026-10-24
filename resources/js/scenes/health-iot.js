// Comel: beating heart, live ECG line, a small circuit board and temperature / oxygen gauges.
import * as THREE from 'three';
import { std, heart, roundedBox, float } from '../three/helpers';

// ECG wave shape for phase 0..1 (P, QRS, T)
function ecg(p) {
    const g = (c, w, h) => h * Math.exp(-((p - c) ** 2) / (2 * w * w));
    return g(0.18, 0.025, 0.12) - g(0.3, 0.008, 0.12) + g(0.33, 0.012, 0.9) - g(0.36, 0.01, 0.25) + g(0.55, 0.04, 0.22);
}

function gaugeTexture() {
    const c = document.createElement('canvas');
    c.width = c.height = 256;
    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;
    return { c, tex };
}

export default function create({ group, accentHex }) {
    const heartMesh = heart(0.42, std('#e3173b', { roughness: 0.35, metalness: 0.05, emissive: '#4a0010', emissiveIntensity: 0.5 }));

    // ECG line
    const N = 160;
    const ecgGeo = new THREE.BufferGeometry();
    ecgGeo.setAttribute('position', new THREE.BufferAttribute(new Float32Array(N * 3), 3));
    const ecgLine = new THREE.Line(ecgGeo, new THREE.LineBasicMaterial({ color: '#4dffb0', toneMapped: false }));

    // Circuit board
    const board = new THREE.Group();
    board.add(roundedBox(0.62, 0.42, 0.03, 0.03, std('#0f5e3a', { roughness: 0.6 })));
    const chipMat = std('#111318', { roughness: 0.5 });
    [[-0.12, 0.05, 0.16, 0.16], [0.16, -0.06, 0.12, 0.1], [0.17, 0.12, 0.08, 0.06]].forEach(([x, y, w, h]) => {
        const chip = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.03), chipMat);
        chip.position.set(x, y, 0.03);
        board.add(chip);
    });
    const traceMat = std('#d9b45a', { metalness: 0.9, roughness: 0.3 });
    [[-0.02, -0.12, 0.4, 0.012, 0], [0.02, 0.15, 0.012, 0.18, 0], [-0.22, -0.05, 0.012, 0.2, 0]].forEach(([x, y, w, h]) => {
        const tr = new THREE.Mesh(new THREE.BoxGeometry(w, h, 0.005), traceMat);
        tr.position.set(x, y, 0.02);
        board.add(tr);
    });
    const led = new THREE.Mesh(new THREE.SphereGeometry(0.02, 12, 8), new THREE.MeshBasicMaterial({ color: '#4dffb0' }));
    led.position.set(-0.24, 0.15, 0.035);
    board.add(led);

    // Gauges (temperature, oxygen) drawn on canvas; arcs move, no numbers (illustration only)
    const gauges = ['TEMP', 'SpO₂'].map((name) => {
        const { c, tex } = gaugeTexture();
        const mesh = new THREE.Mesh(new THREE.PlaneGeometry(0.42, 0.42), new THREE.MeshBasicMaterial({ map: tex, transparent: true, toneMapped: false }));
        return { name, c, tex, mesh };
    });
    const drawGauge = (gg, v) => {
        const x = gg.c.getContext('2d');
        x.clearRect(0, 0, 256, 256);
        x.fillStyle = 'rgba(10,14,28,0.85)';
        x.beginPath();
        x.arc(128, 128, 120, 0, Math.PI * 2);
        x.fill();
        x.lineCap = 'round';
        x.lineWidth = 18;
        x.strokeStyle = 'rgba(255,255,255,0.15)';
        x.beginPath();
        x.arc(128, 136, 80, Math.PI * 0.8, Math.PI * 2.2);
        x.stroke();
        x.strokeStyle = gg.name === 'TEMP' ? '#ffb547' : accentHex;
        x.beginPath();
        x.arc(128, 136, 80, Math.PI * 0.8, Math.PI * (0.8 + 1.4 * v));
        x.stroke();
        x.fillStyle = '#fff';
        x.font = '700 34px "Plus Jakarta Sans", system-ui, sans-serif';
        x.textAlign = 'center';
        x.fillText(gg.name, 128, 150);
        gg.tex.needsUpdate = true;
    };

    group.add(heartMesh, ecgLine, board, ...gauges.map((g) => g.mesh));
    let spread = 1;
    let lastGauge = -1;

    return {
        layout(l) {
            spread = l.spread;
            heartMesh.position.set(-spread * 0.8, 0.55, l.front);
            board.position.set(spread * 0.78, -0.55, l.front - 0.15);
            board.rotation.set(-0.3, -0.5, 0.1);
            gauges[0].mesh.position.set(spread * 0.82, 0.62, l.front - 0.1);
            gauges[1].mesh.position.set(-spread * 0.8, -0.35, l.front - 0.1);
            this.base = { heart: 0.55, g0: 0.62, g1: -0.35 };
        },
        update(t) {
            // Heart beat (~72 bpm, double bump)
            const p = (t * 1.2) % 1;
            const beat = Math.exp(-((p - 0.05) ** 2) / 0.002) + 0.6 * Math.exp(-((p - 0.22) ** 2) / 0.002);
            heartMesh.scale.setScalar(1 + beat * 0.14);
            heartMesh.rotation.y = Math.sin(t * 0.6) * 0.4;

            // Scrolling ECG across the bottom
            const pos = ecgGeo.attributes.position;
            for (let i = 0; i < N; i++) {
                const u = i / (N - 1);
                const x = (u * 2 - 1) * spread * 0.95;
                const phase = (u * 2.2 - t * 1.2) % 1;
                pos.setXYZ(i, x, -0.95 + ecg(phase < 0 ? phase + 1 : phase) * 0.35, 0.45);
            }
            pos.needsUpdate = true;

            led.material.color.set(Math.sin(t * 6) > 0 ? '#4dffb0' : '#0c3a26');
            if (this.base) {
                float(board, t, { base: -0.55, amp: 0.03, speed: 0.9 });
                float(gauges[0].mesh, t, { base: this.base.g0, amp: 0.04, speed: 1.1, phase: 1 });
                float(gauges[1].mesh, t, { base: this.base.g1, amp: 0.04, speed: 1, phase: 2 });
            }
            if (t - lastGauge > 0.1) {
                lastGauge = t;
                drawGauge(gauges[0], 0.55 + Math.sin(t * 0.7) * 0.08);
                drawGauge(gauges[1], 0.82 + Math.sin(t * 0.9 + 1) * 0.06);
            }
        },
    };
}
