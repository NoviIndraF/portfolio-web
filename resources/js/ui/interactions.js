// Small interactions for the public pages. Each init does nothing when its markup is absent.
// Motion is skipped for prefers-reduced-motion; content is complete without JavaScript.

/** Hero: "Now showing: <project>" follows the phone's active screen. */
export function initHeroLabel() {
    const label = document.querySelector('[data-hero-label]');
    const phone = document.querySelector('[data-hero-phone]');
    if (!label || !phone) return;
    const items = JSON.parse(label.dataset.items || '[]');
    const title = label.querySelector('[data-hero-label-title]');
    phone.addEventListener('phone:screen', (e) => {
        const item = items[e.detail.index];
        if (!item) return;
        label.href = item.href;
        title.textContent = item.title;
    });
}

/** Stats count up from 0 the first time they are visible ("10+" keeps its "+"). */
export function initCountUp({ reduced }) {
    if (reduced) return;
    const io = new IntersectionObserver((entries) => {
        entries.forEach(({ isIntersecting, target }) => {
            if (!isIntersecting) return;
            io.unobserve(target);
            const [, before, num, after] = target.textContent.trim().match(/^(\D*)(\d+)(.*)$/) || [];
            if (!num) return;
            const end = Number(num);
            const start = performance.now();
            const step = (now) => {
                const k = Math.min(1, (now - start) / 1200);
                target.textContent = before + Math.round(end * (1 - (1 - k) ** 3)) + after;
                if (k < 1) requestAnimationFrame(step);
            };
            requestAnimationFrame(step);
        });
    });
    document.querySelectorAll('[data-countup]').forEach((el) => io.observe(el));
}

/** Skills: a skill lists the projects that use it and highlights them in the carousel. */
export function initSkillLinks() {
    const result = document.querySelector('[data-skill-result]');
    if (!result) return;
    const label = result.querySelector('[data-skill-result-label]');
    const links = result.querySelector('[data-skill-result-links]');
    const usedIn = document.querySelector('[data-skill-used-in]')?.innerHTML ?? ':skill';
    const buttons = [...document.querySelectorAll('[data-skill-projects]')];
    const cards = [...document.querySelectorAll('[data-project-carousel] [data-project-card]')];

    const select = (button) => {
        buttons.forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
        const projects = button ? JSON.parse(button.dataset.skillProjects) : [];
        const slugs = projects.map((p) => p.slug);
        cards.forEach((c) => {
            c.toggleAttribute('data-match', !!button && slugs.includes(c.dataset.slug));
            c.toggleAttribute('data-dim', !!button && !slugs.includes(c.dataset.slug));
        });
        result.classList.toggle('hidden', !button);
        result.classList.toggle('flex', !!button);
        if (!button) return;
        label.textContent = usedIn.replace(':skill', button.dataset.skill) + ':';
        links.replaceChildren(...projects.map((p) => {
            const a = document.createElement('a');
            a.href = p.href;
            a.textContent = p.title;
            a.className = 'chip border-primary/40 font-semibold hover:border-primary';
            return a;
        }));
        // Bring the first matching card into view inside the carousel (no page jump).
        const first = cards.find((c) => slugs.includes(c.dataset.slug));
        const rail = first?.closest('[data-rail]');
        if (rail) rail.scrollTo({ left: first.offsetLeft - rail.offsetLeft - 16, behavior: 'smooth' });
    };

    buttons.forEach((b) => b.addEventListener('click', () => select(b.getAttribute('aria-pressed') === 'true' ? null : b)));
    result.querySelector('[data-skill-clear]')?.addEventListener('click', () => select(null));
}

/** Highlights the menu link of the section in view; thin progress bar under the header. */
export function initScrollSpy() {
    document.querySelectorAll('[data-scrollspy]').forEach((nav) => {
        // [link, section it points to]; a link may target the section or a heading inside it.
        const links = [...nav.querySelectorAll('a[href*="#"]')]
            .map((a) => [a, document.getElementById(decodeURIComponent(a.hash.slice(1)))?.closest('section')])
            .filter(([, section]) => section);
        if (!links.length) return;
        const io = new IntersectionObserver((entries) => {
            entries.forEach(({ isIntersecting, target }) => {
                if (!isIntersecting) return;
                links.forEach(([a, t]) => a.setAttribute('aria-current', String(t === target)));
                // Keep the active pill visible in a horizontally scrolling nav (project page).
                links.find(([, t]) => t === target)?.[0].scrollIntoView({ block: 'nearest', inline: 'nearest' });
            });
        }, { rootMargin: '-40% 0px -55% 0px' });
        links.forEach(([, section]) => io.observe(section));
    });

    const bar = document.querySelector('[data-scroll-progress]');
    if (!bar) return;
    let queued = false;
    const update = () => {
        queued = false;
        const max = document.documentElement.scrollHeight - innerHeight;
        bar.style.transform = `scaleX(${max > 0 ? Math.min(1, scrollY / max) : 0})`;
    };
    // No measuring at load (it forces a layout before first paint); the bar starts at 0 anyway.
    addEventListener('scroll', () => {
        if (!queued) {
            queued = true;
            requestAnimationFrame(update);
        }
    }, { passive: true });
}

/** Phones: CV + contact buttons appear after the first screen, hidden again near the contact section. */
export function initFloatingCta() {
    const cta = document.querySelector('[data-float-cta]');
    if (!cta) return;
    const contact = document.querySelector('[data-copy]')?.closest('section');
    let nearContact = false;
    if (contact) {
        new IntersectionObserver(([e]) => {
            nearContact = e.isIntersecting;
            toggle();
        }).observe(contact);
    }
    const toggle = () => {
        const show = scrollY > innerHeight * 0.8 && !nearContact;
        cta.toggleAttribute('data-show', show);
        cta.inert = !show;
    };
    addEventListener('scroll', toggle, { passive: true });
    toggle();
}

/** Copy the email address, with a short "Copied" confirmation. */
export function initCopy() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        const toast = button.querySelector('[data-copy-toast]');
        button.addEventListener('click', async () => {
            try {
                await navigator.clipboard.writeText(button.dataset.copy);
            } catch {
                return; // clipboard blocked: the email link next to it still works
            }
            toast.textContent = button.dataset.copiedLabel;
            toast.classList.remove('hidden');
            clearTimeout(button.copyTimer);
            button.copyTimer = setTimeout(() => toast.classList.add('hidden'), 1600);
        });
    });
}

/** Timeline: the line fills while scrolling and each dot lights up when its item is reached. */
export function initTimeline({ reduced }) {
    if (reduced) return;
    document.querySelectorAll('[data-timeline]').forEach((wrap) => {
        const line = wrap.querySelector('[data-timeline-line]');
        const dots = [...wrap.querySelectorAll('[data-timeline-dot]')];
        let queued = false;
        const update = () => {
            queued = false;
            const r = wrap.getBoundingClientRect();
            const progress = Math.min(1, Math.max(0, (innerHeight * 0.7 - r.top) / r.height));
            line.style.transform = `scaleY(${progress})`;
            dots.forEach((d) => d.classList.toggle('opacity-30', d.getBoundingClientRect().top > innerHeight * 0.7));
        };
        // Starts on the first scroll: timelines are below the first screen.
        addEventListener('scroll', () => {
            if (!queued) {
                queued = true;
                requestAnimationFrame(update);
            }
        }, { passive: true });
    });
}

/** Project cards: on mouse hover, cycle through a few screenshots as a quick preview. */
export function initCardPreviews({ reduced }) {
    if (reduced) return;
    document.querySelectorAll('[data-project-card][data-previews]').forEach((card) => {
        const img = card.querySelector('.phone-screen img');
        if (!img) return;
        const urls = JSON.parse(card.dataset.previews);
        const original = { src: img.src, srcset: img.srcset };
        let timer;
        let i = 0;
        card.addEventListener('pointerenter', (e) => {
            if (e.pointerType !== 'mouse') return;
            urls.forEach((u) => (new Image().src = u)); // warm the cache
            timer = setInterval(() => {
                i = (i + 1) % urls.length;
                img.srcset = '';
                img.src = urls[i];
            }, 1100);
        });
        card.addEventListener('pointerleave', () => {
            clearInterval(timer);
            i = 0;
            img.srcset = original.srcset;
            img.src = original.src;
        });
    });
}

/** Project page: "See this screen" scrolls to the phone and switches it to the feature's screenshot. */
export function initFeatureScreens() {
    const phone = document.querySelector('[data-phone-viewer]');
    if (!phone) return;
    document.querySelectorAll('[data-feature-card][data-screen-index] [data-show-screen]').forEach((button) => {
        button.addEventListener('click', () => {
            const index = Number(button.closest('[data-feature-card]').dataset.screenIndex);
            phone.scrollIntoView({ behavior: 'smooth', block: 'center' });
            phone.dispatchEvent(new CustomEvent('phone:goto', { detail: { index } }));
        });
    });
}
