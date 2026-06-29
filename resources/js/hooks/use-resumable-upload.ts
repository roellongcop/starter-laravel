import axios, { type AxiosError } from 'axios';
import { useCallback, useEffect, useRef, useState } from 'react';

import { toast } from '@/hooks/use-toast';

/**
 * Google-Drive-style resumable chunked uploads. The file is sliced and each chunk
 * is PUT to the gated /uploads endpoints; the server (UploadController) remembers
 * which parts arrived, so a drop, an offline blip, or a page reload resumes from
 * the gap instead of restarting. The in-flight session token is parked in
 * localStorage keyed by name+size+lastModified so the same file resumes later.
 */

export type ResumableStatus =
    | 'queued'
    | 'preparing'
    | 'uploading'
    | 'resuming'
    | 'paused'
    | 'assembling'
    | 'done'
    | 'error';

export interface ResumableItem {
    key: string;
    name: string;
    progress: number;
    status: ResumableStatus;
    error?: string;
}

interface SessionState {
    token: string;
    chunk_size: number;
    total_chunks: number;
    received_parts: number[];
    received_bytes: number;
    size: number;
    status: string;
    file?: unknown;
}

const CONCURRENCY = 3;
const MAX_ATTEMPTS = 6;

function storageKey(file: File): string {
    return `resumable-upload:${file.name}:${file.size}:${file.lastModified}`;
}

function sleep(ms: number): Promise<void> {
    return new Promise((resolve) => setTimeout(resolve, ms));
}

/**
 * Drives one file's upload. Owns pause/resume/abort and the offline gate; emits
 * progress/status through `onChange`. Not a hook — pure imperative I/O the hook
 * wraps per queued file.
 */
class UploadController {
    private paused = false;

    private aborted = false;

    private wake: (() => void) | null = null;

    constructor(
        private readonly file: File,
        private readonly data: Record<string, string | number> | undefined,
        private readonly onChange: (patch: Partial<ResumableItem>) => void,
    ) {}

    pause(): void {
        this.paused = true;
        this.onChange({ status: 'paused' });
    }

    resume(): void {
        this.paused = false;
        this.onChange({ status: 'uploading' });
        this.wakeUp();
    }

    abort(): void {
        this.aborted = true;
        this.wakeUp();
    }

    /** Nudge a controller stuck in the offline gate once the network returns. */
    notifyOnline(): void {
        this.wakeUp();
    }

    async run(): Promise<unknown> {
        this.onChange({ status: 'preparing', progress: 0 });

        const session = await this.ensureSession();

        // A session can already be finished server-side (resumed after the
        // complete call landed but before we recorded it) — adopt its File.
        if (session.status === 'Done' && session.file) {
            this.clearStored();
            this.onChange({ status: 'done', progress: 100 });
            return session.file;
        }

        const { chunk_size: chunkSize, total_chunks: total } = session;
        const totalBytes = this.file.size;
        const received = new Set(session.received_parts);

        let ackedBytes = session.received_bytes ?? 0;
        const inflight = new Map<number, number>();

        const report = (): void => {
            let loaded = ackedBytes;
            for (const bytes of inflight.values()) {
                loaded += bytes;
            }
            const pct =
                totalBytes === 0
                    ? 100
                    : Math.min(99, Math.round((loaded / totalBytes) * 100));
            this.onChange({
                progress: pct,
                status: this.paused ? 'paused' : 'uploading',
            });
        };
        report();

        const missing: number[] = [];
        for (let part = 1; part <= total; part++) {
            if (!received.has(part)) {
                missing.push(part);
            }
        }

        let cursor = 0;
        const worker = async (): Promise<void> => {
            while (cursor < missing.length) {
                const part = missing[cursor++];
                await this.gate();

                const start = (part - 1) * chunkSize;
                const blob = this.file.slice(
                    start,
                    Math.min(start + chunkSize, totalBytes),
                );

                await this.putPart(session.token, part, blob, (loaded) => {
                    inflight.set(part, loaded);
                    report();
                });

                inflight.delete(part);
                ackedBytes += blob.size;
                report();
            }
        };

        const workers = Math.min(CONCURRENCY, Math.max(1, missing.length));
        await Promise.all(Array.from({ length: workers }, worker));

        this.onChange({ status: 'assembling' });
        const file = await this.complete(session.token);

        this.clearStored();
        this.onChange({ status: 'done', progress: 100 });

        return file;
    }

    /** Block while paused or offline; reject if aborted. */
    private async gate(): Promise<void> {
        while (!this.aborted && (this.paused || !navigator.onLine)) {
            if (!this.paused && !navigator.onLine) {
                this.onChange({ status: 'resuming' });
            }
            await new Promise<void>((resolve) => {
                this.wake = resolve;
                // Fallback poll so a missed online/resume event can't wedge us.
                window.setTimeout(resolve, 2000);
            });
        }

        if (this.aborted) {
            throw new DOMException('Upload aborted', 'AbortError');
        }
    }

    private wakeUp(): void {
        const wake = this.wake;
        this.wake = null;
        wake?.();
    }

    private async ensureSession(): Promise<SessionState> {
        const stored = localStorage.getItem(storageKey(this.file));

        if (stored) {
            try {
                const { data } = await axios.get<SessionState>(
                    route('uploads.show', stored),
                );
                if (data.status !== 'Failed' && data.status !== 'Aborted') {
                    return data;
                }
            } catch {
                // Token is stale/expired — fall through and start fresh.
            }
            this.clearStored();
        }

        const { data } = await axios.post<SessionState>(
            route('uploads.store'),
            {
                original_name: this.file.name,
                size: this.file.size,
                mime: this.file.type || null,
                ...(this.data ?? {}),
            },
        );

        localStorage.setItem(storageKey(this.file), data.token);

        return data;
    }

    private async putPart(
        token: string,
        part: number,
        blob: Blob,
        onProgress: (loaded: number) => void,
    ): Promise<void> {
        for (let attempt = 1; ; attempt++) {
            await this.gate();

            try {
                await axios.put(
                    route('uploads.part', { uploadSession: token, part }),
                    blob,
                    {
                        headers: { 'Content-Type': 'application/octet-stream' },
                        onUploadProgress: (event) => onProgress(event.loaded),
                    },
                );

                return;
            } catch (error) {
                const status = (error as AxiosError).response?.status;

                // 4xx (bad chunk, forbidden, expired) is fatal — only retry on
                // network errors, timeouts, throttling, and 5xx.
                const retryable =
                    status === undefined ||
                    status >= 500 ||
                    status === 408 ||
                    status === 429;
                if (!retryable || attempt >= MAX_ATTEMPTS) {
                    throw error;
                }

                this.onChange({ status: 'resuming' });
                await sleep(Math.min(1000 * 2 ** attempt, 15000));
            }
        }
    }

    private async complete(token: string): Promise<unknown> {
        // First call kicks off assembly (or finishes inline); then poll while the
        // server assembles a large local-disk upload.
        for (let poll = 0; ; poll++) {
            await this.gate();

            if (poll === 0) {
                const { data, status } = await axios.post<{
                    status: string;
                    file?: unknown;
                }>(route('uploads.complete', token), {});
                if (status === 200 && data.status === 'done') {
                    return data.file;
                }
            }

            await sleep(1500);
            const { data } = await axios.get<SessionState>(
                route('uploads.show', token),
            );
            if (data.status === 'Done' && data.file) {
                return data.file;
            }
            if (data.status === 'Failed' || data.status === 'Aborted') {
                throw new Error('The upload could not be finalized.');
            }
        }
    }

    private clearStored(): void {
        localStorage.removeItem(storageKey(this.file));
    }
}

interface UseResumableUploadOptions {
    data?: Record<string, string | number>;
    onUploaded: (file: unknown) => void;
}

export function useResumableUpload({
    data,
    onUploaded,
}: UseResumableUploadOptions) {
    const [items, setItems] = useState<ResumableItem[]>([]);
    const controllers = useRef<Map<string, UploadController>>(new Map());
    const files = useRef<Map<string, File>>(new Map());

    const dataRef = useRef(data);
    dataRef.current = data;
    const onUploadedRef = useRef(onUploaded);
    onUploadedRef.current = onUploaded;

    const patch = useCallback((key: string, value: Partial<ResumableItem>) => {
        setItems((queue) =>
            queue.map((item) =>
                item.key === key ? { ...item, ...value } : item,
            ),
        );
    }, []);

    const remove = useCallback((key: string) => {
        controllers.current.delete(key);
        files.current.delete(key);
        setItems((queue) => queue.filter((item) => item.key !== key));
    }, []);

    const run = useCallback(
        (key: string, file: File) => {
            const controller = new UploadController(
                file,
                dataRef.current,
                (value) => patch(key, value),
            );
            controllers.current.set(key, controller);

            controller
                .run()
                .then((fileRow) => {
                    onUploadedRef.current(fileRow);
                    toast({
                        variant: 'success',
                        description: `Uploaded ${file.name}`,
                    });
                    // Leave the 100% bar up briefly, then drop the row.
                    window.setTimeout(() => remove(key), 1200);
                })
                .catch((error: unknown) => {
                    if (
                        error instanceof DOMException &&
                        error.name === 'AbortError'
                    ) {
                        return;
                    }
                    patch(key, {
                        status: 'error',
                        error: 'Upload failed. Click retry to resume.',
                    });
                });
        },
        [patch, remove],
    );

    const start = useCallback(
        (file: File) => {
            const key = `${file.name}-${file.size}-${file.lastModified}`;
            files.current.set(key, file);
            setItems((queue) =>
                queue.some((item) => item.key === key)
                    ? queue
                    : [
                          ...queue,
                          {
                              key,
                              name: file.name,
                              progress: 0,
                              status: 'queued',
                          },
                      ],
            );
            run(key, file);
        },
        [run],
    );

    const pause = useCallback(
        (key: string) => controllers.current.get(key)?.pause(),
        [],
    );
    const resume = useCallback(
        (key: string) => controllers.current.get(key)?.resume(),
        [],
    );

    const retry = useCallback(
        (key: string) => {
            const file = files.current.get(key);
            if (!file) {
                return;
            }
            patch(key, { status: 'queued', progress: 0, error: undefined });
            run(key, file);
        },
        [patch, run],
    );

    const dismiss = useCallback(
        (key: string) => {
            controllers.current.get(key)?.abort();
            remove(key);
        },
        [remove],
    );

    // Auto-resume any upload waiting in the offline gate the moment we reconnect.
    useEffect(() => {
        const onOnline = () =>
            controllers.current.forEach((controller) =>
                controller.notifyOnline(),
            );
        window.addEventListener('online', onOnline);

        return () => window.removeEventListener('online', onOnline);
    }, []);

    return { items, start, pause, resume, retry, dismiss };
}
