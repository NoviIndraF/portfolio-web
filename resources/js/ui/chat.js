// "Ask about me" AI chatbot: sends the question to POST /chat and shows the answer.
export function initChat() {
    const root = document.querySelector('[data-chat]');
    if (!root) return;

    const panel = root.querySelector('[data-chat-panel]');
    const toggle = root.querySelector('[data-chat-toggle]');
    const log = root.querySelector('[data-chat-log]');
    const form = root.querySelector('[data-chat-form]');
    const input = form.querySelector('input');
    const quick = root.querySelector('[data-chat-quick]');
    const avatar = root.querySelector('[data-chat-avatar]');
    const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let busy = false;

    // Greeting next to the button: once per visit (sessionStorage), gone after it is closed or the chat is opened.
    const teaser = root.querySelector('[data-chat-teaser]');
    const hideTeaser = () => {
        teaser.hidden = true;
        try {
            sessionStorage.setItem('chat-teaser-seen', '1');
        } catch {}
    };
    let teaserSeen = false;
    try {
        teaserSeen = sessionStorage.getItem('chat-teaser-seen') === '1';
    } catch {}
    if (!teaserSeen) {
        setTimeout(() => {
            if (panel.hidden) teaser.hidden = false;
        }, 4000);
    }

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) {
            hideTeaser();
            input.focus();
        }
    };
    teaser.querySelector('[data-chat-teaser-open]').addEventListener('click', () => setOpen(true));
    teaser.querySelector('[data-chat-teaser-close]').addEventListener('click', hideTeaser);

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('[data-chat-close]').addEventListener('click', () => {
        setOpen(false);
        toggle.focus();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) setOpen(false);
    });

    // Text only (textContent): the AI answer is never rendered as HTML, so it cannot inject scripts.
    // AI answers sit in a row with the avatar; user messages stand alone on the right.
    const bubble = (role, text) => {
        const el = document.createElement('p');
        el.className = role === 'user' ? 'chat-bubble-user chat-gradient chat-in' : 'chat-bubble';
        el.textContent = text;
        let row = el;
        if (role !== 'user') {
            row = document.createElement('div');
            row.className = 'chat-row chat-in';
            row.append(avatar.cloneNode(true), el);
        }
        log.appendChild(row);
        log.scrollTop = log.scrollHeight;
        return el;
    };

    // Types the formatted answer out a few characters per frame, like a streaming AI reply (about 3 s at most).
    const typeOut = async (el) => {
        if (reduceMotion.matches) {
            log.scrollTop = log.scrollHeight;
            return;
        }
        const walker = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        const parts = [];
        while (walker.nextNode()) parts.push([walker.currentNode, walker.currentNode.data]);
        const step = Math.max(2, Math.ceil(el.textContent.length / 180));
        parts.forEach(([node]) => (node.data = ''));
        el.classList.add('chat-caret');
        for (const [node, full] of parts) {
            for (let i = step; i < full.length + step; i += step) {
                // Follow the text only while the reader stays at the bottom, so scrolling up to reread works.
                const atBottom = log.scrollHeight - log.scrollTop - log.clientHeight < 48;
                node.data = full.slice(0, i);
                if (atBottom) log.scrollTop = log.scrollHeight;
                await new Promise(requestAnimationFrame);
            }
        }
        el.classList.remove('chat-caret');
    };

    // Light formatting for an answer: links, e-mail addresses and **bold**.
    // Built from DOM nodes, so text from the AI is still never parsed as HTML.
    const TOKENS = /(https?:\/\/[^\s)]+|[\w.+-]+@[\w-]+\.[\w.-]+|\*\*[^*]+\*\*)/g;

    const link = (href, label, external) => {
        const a = document.createElement('a');
        a.href = href;
        a.textContent = label;
        a.className = 'font-semibold text-primary-fg underline break-all';
        if (external) {
            a.target = '_blank';
            a.rel = 'noopener';
        }
        return a;
    };

    const format = (el, text) => {
        el.textContent = '';
        text = text.replace(/^([ \t]*)[*-] /gm, '$1• '); // markdown list items ("* " or "- ") become bullets
        text.split(TOKENS).forEach((part, i) => {
            if (i % 2 === 0) {
                el.append(part);
                return;
            }
            if (part.startsWith('**')) {
                const strong = document.createElement('strong');
                format(strong, part.slice(2, -2)); // a bold e-mail or URL still becomes a link
                el.append(strong);
                return;
            }
            const trail = part.match(/[.,;:!?]+$/)?.[0] ?? '';
            const value = part.slice(0, part.length - trail.length);
            if (value.includes('@') && !value.startsWith('http')) {
                el.append(link(`mailto:${value}`, value, false), trail);
                return;
            }
            const url = new URL(value);
            const ownSite = url.origin === location.origin;
            // Links to this site show only the path, e.g. "/projects/codelytic".
            el.append(link(value, ownSite ? url.pathname : value, !ownSite), trail);
        });
    };

    // The conversation survives page changes during the visit (sessionStorage, last 20 messages).
    let history = [];
    try {
        history = (JSON.parse(sessionStorage.getItem('chat-history')) || []).filter((m) => typeof m?.text === 'string');
    } catch {}
    const remember = (...messages) => {
        history = [...history, ...messages].slice(-20);
        try {
            sessionStorage.setItem('chat-history', JSON.stringify(history));
        } catch {}
    };
    history.forEach(({ role, text }) => {
        const el = bubble(role, text);
        if (role !== 'user') format(el, text);
    });
    if (history.length) quick.hidden = true;

    async function send(message) {
        if (busy || !message) return;
        busy = true;
        quick.hidden = true;
        bubble('user', message);
        const answer = bubble('assistant', '');
        answer.innerHTML = '<span class="chat-typing"><i></i><i></i><i></i></span>'; // static markup, no AI text
        // Busy on the live region, so screen readers read the answer once it is fully typed.
        log.setAttribute('aria-busy', 'true');
        root.dataset.state = 'thinking'; // the robot reacts: see .robot-live in app.css

        try {
            const res = await fetch(root.dataset.chatUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message }),
            });
            const data = await res.json().catch(() => ({}));
            format(answer, data.reply || data.message || root.dataset.chatError);
            // Only real answers are kept; errors and limits are not worth restoring on the next page.
            if (data.reply) remember({ role: 'user', text: message }, { role: 'assistant', text: data.reply });
            root.dataset.state = 'talking';
            await typeOut(answer);
        } catch {
            answer.classList.remove('chat-caret');
            answer.textContent = root.dataset.chatError;
        } finally {
            delete root.dataset.state;
            log.removeAttribute('aria-busy');
            busy = false;
        }
    }

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const message = input.value.trim();
        input.value = '';
        send(message);
    });

    quick.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-chat-question]');
        if (btn) send(btn.dataset.chatQuestion);
    });
}
