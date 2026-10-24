// Screen textures: screenshots fitted like CSS "object-fit: cover; object-position: top",
// or an animated placeholder screen for apps without public screenshots.
import * as THREE from 'three';

const loader = new THREE.TextureLoader();

export function loadScreen(url, screenAspect, renderer) {
    return new Promise((resolve, reject) => {
        loader.load(
            url,
            (tex) => {
                tex.colorSpace = THREE.SRGBColorSpace;
                tex.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
                fitCoverTop(tex, screenAspect);
                resolve(tex);
            },
            undefined,
            reject,
        );
    });
}

function fitCoverTop(tex, screenAspect) {
    const a = tex.image.width / tex.image.height;
    if (a > screenAspect) {
        tex.repeat.set(screenAspect / a, 1);
        tex.offset.set((1 - screenAspect / a) / 2, 0);
    } else {
        tex.repeat.set(1, a / screenAspect);
        tex.offset.set(0, 1 - a / screenAspect); // keep the top of the screenshot
    }
}

/** Animated placeholder: app name + sensor gauges (hydroponic etc.) or neutral bars (analytics). */
export function placeholderScreen({ title, accent, scene }) {
    const c = document.createElement('canvas');
    c.width = 460;
    c.height = 1000;
    const g = c.getContext('2d');
    const tex = new THREE.CanvasTexture(c);
    tex.colorSpace = THREE.SRGBColorSpace;

    const draw = (t) => {
        const grd = g.createRadialGradient(230, 0, 40, 230, 120, 900);
        grd.addColorStop(0, accent);
        grd.addColorStop(0.55, '#070a14');
        g.fillStyle = grd;
        g.fillRect(0, 0, c.width, c.height);

        // App icon + name
        g.fillStyle = accent;
        g.beginPath();
        g.roundRect(40, 120, 64, 64, 16);
        g.fill();
        g.fillStyle = '#05070d';
        g.font = '800 34px "Plus Jakarta Sans", system-ui, sans-serif';
        g.textAlign = 'center';
        g.textBaseline = 'middle';
        g.fillText(title.charAt(0), 72, 154);
        g.fillStyle = '#fff';
        g.textAlign = 'left';
        g.font = '700 32px "Plus Jakarta Sans", system-ui, sans-serif';
        g.fillText(title, 122, 154);

        if (scene === 'analytics') {
            // Neutral abstract bars
            const base = 640;
            for (let i = 0; i < 6; i++) {
                const h = 120 + 150 * (0.5 + 0.5 * Math.sin(t * 1.2 + i * 0.9));
                g.fillStyle = i % 2 ? accent : 'rgba(255,255,255,0.75)';
                g.beginPath();
                g.roundRect(48 + i * 64, base - h, 44, h, 10);
                g.fill();
            }
        } else {
            // Sensor gauges (no numbers: illustration only)
            ['Temp', 'Water', 'Humidity', 'Light'].forEach((name, i) => {
                const x = 120 + (i % 2) * 220;
                const y = 340 + Math.floor(i / 2) * 230;
                const v = 0.55 + 0.25 * Math.sin(t * 0.9 + i * 1.3);
                g.fillStyle = 'rgba(255,255,255,0.06)';
                g.beginPath();
                g.roundRect(x - 95, y - 95, 190, 200, 24);
                g.fill();
                g.lineWidth = 14;
                g.lineCap = 'round';
                g.strokeStyle = 'rgba(255,255,255,0.12)';
                g.beginPath();
                g.arc(x, y - 5, 55, 0, Math.PI * 2);
                g.stroke();
                g.strokeStyle = accent;
                g.beginPath();
                g.arc(x, y - 5, 55, -Math.PI / 2, -Math.PI / 2 + Math.PI * 2 * v);
                g.stroke();
                g.fillStyle = 'rgba(255,255,255,0.8)';
                g.font = '600 22px "Plus Jakarta Sans", system-ui, sans-serif';
                g.textAlign = 'center';
                g.fillText(name, x, y + 80);
            });
        }

        // Skeleton lines
        g.fillStyle = 'rgba(255,255,255,0.14)';
        g.beginPath();
        g.roundRect(48, 860, 260, 14, 7);
        g.fill();
        g.fillStyle = 'rgba(255,255,255,0.09)';
        g.beginPath();
        g.roundRect(48, 890, 190, 14, 7);
        g.fill();
        tex.needsUpdate = true;
    };
    draw(0);
    return { texture: tex, draw };
}
