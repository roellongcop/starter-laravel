import { router } from '@inertiajs/react';

// A small per-tab (sessionStorage) in-app navigation stack so <BackButton> can
// revisit the previous page with a FRESH router.get instead of history.back().
// Mechanism + the {replace:true} caveat: docs/conventions/frontend.md
// ("Navigation history").
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

    // {replace:true} reloads (filter/sort) skip the `navigate` event, so mirror
    // them here: same path as the stack top but a different query string →
    // replace the top (see frontend.md "Navigation history").
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
