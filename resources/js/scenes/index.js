// One module per accent scene, loaded only when a page needs it.
// Each module default-exports create(ctx) → { update(t, dt), layout({ spread, front }), clickables?, onClick? }.
const scenes = {
    hero: () => import('./hero-stack.js'),
    learning: () => import('./learning.js'),
    donation: () => import('./donation.js'),
    'shoe-store': () => import('./shoe-store.js'),
    chat: () => import('./chat.js'),
    'health-iot': () => import('./health-iot.js'),
    hydroponic: () => import('./hydroponic.js'),
    analytics: () => import('./analytics.js'),
    generic: () => import('./generic.js'),
    // 'none': no accent scene, phone only
};

export async function loadScene(type) {
    const load = scenes[type];
    return load ? (await load()).default : null;
}
