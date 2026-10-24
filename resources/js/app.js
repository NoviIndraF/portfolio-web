// Public site entry. Small UI modules run right away; 3D (Three.js) and motion (GSAP, Lenis)
// are loaded lazily so the first screen stays fast.
import { initNav } from './ui/nav';
import { initRails } from './ui/rail';
import { initPhoneScreens } from './ui/phone-screens';
import { initLightbox } from './ui/lightbox';
import { initCarousel3d, initSpotlight } from './ui/carousel3d';
import { initThree, prefersReducedMotion, onFirstInteraction } from './ui/three-boot';
import { initMotion } from './ui/motion';

document.documentElement.classList.add('js');
const reduced = prefersReducedMotion();

initNav();
initRails();
initPhoneScreens();
initLightbox();
initCarousel3d({ reduced });
initSpotlight();
initThree();
onFirstInteraction(() => initMotion({ reduced }));
