// Adapted from shadcn/ui's use-toast: a module-global toast store so toast() can
// be called from anywhere (components, axios handlers, the Inertia flash bridge).
// See docs/features/notifications-sessions-audit.md.
import * as React from 'react';

import {
    type ToastActionElement,
    type ToastProps,
} from '@/Components/ui/toast';

const TOAST_LIMIT = 4;
const TOAST_REMOVE_DELAY = 5000;

type ToasterToast = Omit<ToastProps, 'title'> & {
    id: string;
    title?: React.ReactNode;
    description?: React.ReactNode;
    action?: ToastActionElement;
};

let count = 0;
function genId(): string {
    count = (count + 1) % Number.MAX_SAFE_INTEGER;
    return count.toString();
}

type State = { toasts: ToasterToast[] };

const listeners: Array<(state: State) => void> = [];
let memoryState: State = { toasts: [] };
const timeouts = new Map<string, ReturnType<typeof setTimeout>>();

function setState(next: State): void {
    memoryState = next;
    listeners.forEach((l) => l(memoryState));
}

function scheduleRemoval(id: string): void {
    if (timeouts.has(id)) return;
    const t = setTimeout(() => {
        timeouts.delete(id);
        setState({ toasts: memoryState.toasts.filter((x) => x.id !== id) });
    }, TOAST_REMOVE_DELAY);
    timeouts.set(id, t);
}

interface ToastInput {
    title?: React.ReactNode;
    description?: React.ReactNode;
    variant?: ToastProps['variant'];
    duration?: number;
}

export function toast(input: ToastInput) {
    const id = genId();

    const dismiss = () =>
        setState({
            toasts: memoryState.toasts.map((t) =>
                t.id === id ? { ...t, open: false } : t,
            ),
        });

    setState({
        toasts: [
            {
                ...input,
                id,
                open: true,
                onOpenChange: (open: boolean) => {
                    if (!open) dismiss();
                },
            },
            ...memoryState.toasts,
        ].slice(0, TOAST_LIMIT),
    });

    scheduleRemoval(id);

    return { id, dismiss };
}

export function useToast() {
    const [state, setLocal] = React.useState<State>(memoryState);

    React.useEffect(() => {
        listeners.push(setLocal);
        return () => {
            const i = listeners.indexOf(setLocal);
            if (i > -1) listeners.splice(i, 1);
        };
    }, []);

    return { toasts: state.toasts, toast };
}
