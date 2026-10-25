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
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    let busy = false;

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', String(open));
        if (open) input.focus();
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));
    root.querySelector('[data-chat-close]').addEventListener('click', () => {
        setOpen(false);
        toggle.focus();
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !panel.hidden) setOpen(false);
    });

    // Text only (textContent): the AI answer is never rendered as HTML, so it cannot inject scripts.
    const bubble = (role, text) => {
        const el = document.createElement('p');
        el.className = role === 'user'
            ? 'max-w-[85%] self-end whitespace-pre-line rounded-2xl rounded-br-md bg-primary px-3.5 py-2.5 text-bg'
            : 'max-w-[85%] self-start whitespace-pre-line rounded-2xl rounded-bl-md bg-surface-2 px-3.5 py-2.5 text-pretty';
        el.textContent = text;
        log.appendChild(el);
        log.scrollTop = log.scrollHeight;
        return el;
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

    async function send(message) {
        if (busy || !message) return;
        busy = true;
        quick.hidden = true;
        bubble('user', message);
        const answer = bubble('assistant', '…');
        answer.setAttribute('aria-busy', 'true');

        try {
            const res = await fetch(root.dataset.chatUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ message }),
            });
            const data = await res.json().catch(() => ({}));
            format(answer, data.reply || data.message || root.dataset.chatError);
        } catch {
            answer.textContent = root.dataset.chatError;
        } finally {
            answer.removeAttribute('aria-busy');
            log.scrollTop = log.scrollHeight;
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
