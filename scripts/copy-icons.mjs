// Copies simple-icons SVGs into public/icons/simple so Blade can inline them
// without node_modules on the server (and without any CDN).
import { cpSync, mkdirSync, existsSync } from 'node:fs';

const src = 'node_modules/simple-icons/icons';
const dest = 'public/icons/simple';

if (!existsSync(src)) {
    console.warn('simple-icons not installed, skipping icon copy');
    process.exit(0);
}
mkdirSync(dest, { recursive: true });
cpSync(src, dest, { recursive: true });
console.log(`Copied simple-icons to ${dest}`);
