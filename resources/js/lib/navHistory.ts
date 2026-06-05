import { router } from '@inertiajs/react';

// A small in-app navigation stack so <BackButton> can revisit the previous page
// with a FRESH server request (router.get) instead of window.history.back(),
// which Inertia restores from its history cache (stale data). Kept in
// sessionStorage so it survives a refresh, and supports multi-level back.
//
// The stack is driven entirely by Inertia's `navigate` event: a navigation to
// the second-from-top URL is treated as a back (pop), anything else as a forward
// (push). Back simply visits previousUrl() and the handler pops it — no flags.
const KEY = 'nav:stack';
const MAX = 50;

function read(): string[] {
    try {
        const raw = sessionStorage.getItem(KEY);
        return raw ? (JSON.parse(raw) as string[]) : [];
    } catch {
        return [];
    }
}

function write(stack: string[]): void {
    try {
        sessionStorage.setItem(KEY, JSON.stringify(stack.slice(-MAX)));
    } catch {
        /* ignore quota/availability errors */
    }
}

function here(): string {
    return window.location.pathname + window.location.search;
}

function samePath(a: string, b: string): boolean {
    return a.split('?')[0] === b.split('?')[0];
}

export function initNavHistory(): void {
    const stack = read();
    const url = here();
    if (stack[stack.length - 1] !== url) {
        stack.push(url);
        write(stack);
    }

    router.on('navigate', (event) => {
        const next = event.detail.page.url;
        const s = read();
        if (s[s.length - 1] === next) return; // same page → ignore
        if (s[s.length - 2] === next)
            s.pop(); // navigated to the previous entry → back
        else s.push(next); // forward
        write(s);
    });

    // Filter/sort reloads use { replace: true }, for which Inertia SKIPS the
    // `navigate` event (core: `if (!replace) fireNavigateEvent`). `success` still
    // fires, so mirror those here: when a visit lands on the same path as the
    // stack top but a different URL (changed query string), replace the top —
    // matching the browser's replaceState. Cross-page pushes/pops and back stay
    // with the `navigate` handler above (different path → ignored here).
    router.on('success', (event) => {
        const url = event.detail.page.url;
        const s = read();
        const top = s[s.length - 1];
        if (top === undefined || top === url) return;
        if (samePath(top, url)) {
            s[s.length - 1] = url;
            write(s);
        }
    });
}

/** The previous page's URL, or null when there's nothing to go back to. */
export function previousUrl(): string | null {
    const stack = read();
    return stack.length >= 2 ? stack[stack.length - 2] : null;
}
