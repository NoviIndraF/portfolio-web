// Public site entry. Small UI modules run right away; 3D (Three.js) and motion (GSAP, Lenis)
// are loaded lazily so the first screen stays fast.
import { initNav } from './ui/nav';
import { initRails } from './ui/rail';
import { initPhoneScreens } from './ui/phone-screens';
import { initLightbox } from './ui/lightbox';
import { initCarousel3d, initSpotlight } from './ui/carousel3d';
import { initThree, prefersReducedMotion, onFirstInteraction } from './ui/three-boot';
import { initMotion } from './ui/motion';
import { initFilter } from './ui/filter';
import { initChat } from './ui/chat';
import { initTheme } from './ui/theme';
import { initHeroLabel, initCountUp, initSkillLinks, initScrollSpy, initFloatingCta, initCopy, initTimeline, initCardPreviews, initFeatureScreens, initPhotoCarousel } from './ui/interactions';

document.documentElement.classList.add('js');
const reduced = prefersReducedMotion();

initTheme();
initNav();
initRails();
initPhoneScreens();
initLightbox();
initCarousel3d({ reduced });
initSpotlight();
initFilter();
initChat();
initHeroLabel();
initCountUp({ reduced });
initSkillLinks();
initScrollSpy();
initFloatingCta();
initPhotoCarousel({ reduced });
initCopy();
initTimeline({ reduced });
initCardPreviews({ reduced });
initFeatureScreens();
initThree();
onFirstInteraction(() => initMotion({ reduced }));
