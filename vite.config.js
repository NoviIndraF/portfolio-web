import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
            fonts: [
                // Downloaded at build time and served from this site (no font CDN at runtime).
                bunny('Plus Jakarta Sans', {
                    alias: 'sans',
                    variable: '--font-jakarta',
                    weights: [400, 500, 600, 700, 800],
                    preload: [{ weight: 400 }, { weight: 700 }],
                }),
                // v2: condensed display face for big uppercase titles, mono for small labels.
                bunny('Anton', { variable: '--font-anton', weights: [400], preload: [{ weight: 400 }] }),
                bunny('JetBrains Mono', { variable: '--font-jetbrains', weights: [400, 500] }),
            ],
        }),
        tailwindcss(),
    ],
    build: {
        // three.js is one lazy-loaded chunk (~150 kB gzip); it never blocks the first paint.
        chunkSizeWarningLimit: 700,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
