import axios from 'axios';
import { FileUp, Pause, Play, RotateCw, X } from 'lucide-react';
import { type ReactNode, useCallback, useState } from 'react';
import { type Accept, useDropzone } from 'react-dropzone';

import {
    type ResumableStatus,
    useResumableUpload,
} from '@/hooks/use-resumable-upload';
import { toast } from '@/hooks/use-toast';
import { cn } from '@/lib/utils';

interface UploadItem {
    key: string;
    name: string;
    progress: number;
    error?: string;
}

interface Props {
    /** Endpoint that accepts the multipart upload and returns the stored record. */
    uploadUrl: string;
    /** react-dropzone accept map. Defaults to pdf/doc/docx. */
    accept?: Accept;
    maxSizeMb?: number;
    multiple?: boolean;
    /** Multipart field name. */
    field?: string;
    /** Extra fields appended to every upload (e.g. a target user_id). */
    data?: Record<string, string | number>;
    hint?: string;
    /** Called with the JSON returned by uploadUrl for each successful upload. */
    onUploaded: (file: unknown) => void;
    /**
     * Enable Google-Drive-style chunked, resumable uploads. Files larger than
     * `maxSizeMb` go through the /uploads endpoints (progress + resume on flaky
     * networks); smaller files stay on the proven single-shot `uploadUrl` path.
     */
    resumable?: boolean;
    /** Max per-file size (MB) accepted in resumable mode. Default 5120 (5 GB). */
    resumableMaxMb?: number;
}

const DEFAULT_ACCEPT: Accept = {
    'application/pdf': ['.pdf'],
    'application/msword': ['.doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
        '.docx',
    ],
};

const STATUS_LABEL: Record<ResumableStatus, string | undefined> = {
    queued: 'Queued',
    preparing: 'Preparing…',
    uploading: undefined,
    resuming: 'Reconnecting…',
    paused: 'Paused',
    assembling: 'Finishing…',
    done: 'Done',
    error: undefined,
};

/**
 * Reusable drag-and-drop uploader; hands each upload's returned JSON to
 * `onUploaded`. Pass `resumable` to chunk large files so they survive a dropped
 * connection. See docs/features/files-and-media.md.
 */
export default function FileDropzone({
    uploadUrl,
    accept = DEFAULT_ACCEPT,
    maxSizeMb = 10,
    multiple = true,
    field = 'file',
    data,
    hint,
    onUploaded,
    resumable = false,
    resumableMaxMb = 5120,
}: Props) {
    const [queue, setQueue] = useState<UploadItem[]>([]);
    const ru = useResumableUpload({ data, onUploaded });

    const update = (key: string, patch: Partial<UploadItem>) =>
        setQueue((q) => q.map((i) => (i.key === key ? { ...i, ...patch } : i)));

    const remove = (key: string) =>
        setQueue((q) => q.filter((i) => i.key !== key));

    const simpleUpload = useCallback(
        (file: File) => {
            const key = `${file.name}-${file.size}-${file.lastModified}`;
            setQueue((q) => [...q, { key, name: file.name, progress: 0 }]);

            const form = new FormData();
            form.append(field, file);
            Object.entries(data ?? {}).forEach(([k, v]) =>
                form.append(k, String(v)),
            );

            axios
                .post(uploadUrl, form, {
                    onUploadProgress: (e) => {
                        if (e.total) {
                            update(key, {
                                progress: Math.round(
                                    (e.loaded / e.total) * 100,
                                ),
                            });
                        }
                    },
                })
                .then((res) => {
                    onUploaded(res.data);
                    remove(key);
                    toast({
                        variant: 'success',
                        description: `Uploaded ${file.name}`,
                    });
                })
                .catch((err) => {
                    update(key, {
                        error:
                            err.response?.status === 422
                                ? 'File type or size not allowed.'
                                : 'Upload failed.',
                    });
                });
        },
        [uploadUrl, field, data, onUploaded],
    );

    const onDrop = useCallback(
        (accepted: File[]) =>
            accepted.forEach((file) => {
                // Large files take the resumable path; small ones stay single-shot.
                if (resumable && file.size > maxSizeMb * 1024 * 1024) {
                    ru.start(file);
                } else {
                    simpleUpload(file);
                }
            }),
        [resumable, maxSizeMb, ru, simpleUpload],
    );

    const { getRootProps, getInputProps, isDragActive, fileRejections } =
        useDropzone({
            onDrop,
            accept,
            multiple,
            maxSize: (resumable ? resumableMaxMb : maxSizeMb) * 1024 * 1024,
        });

    return (
        <div className="space-y-3">
            <div
                {...getRootProps()}
                className={cn(
                    'flex cursor-pointer flex-col items-center justify-center gap-2 rounded-md border-2 border-dashed p-8 text-center text-muted-foreground transition-colors',
                    isDragActive
                        ? 'border-primary bg-primary/5'
                        : 'hover:bg-muted/50',
                )}
            >
                <input {...getInputProps()} />
                <FileUp className="h-7 w-7" />
                <p className="text-sm">
                    {isDragActive
                        ? 'Drop the files here…'
                        : 'Drag files here, or click to browse'}
                </p>
                {hint && <p className="text-xs">{hint}</p>}
            </div>

            {fileRejections.length > 0 && (
                <ul className="space-y-1 text-xs text-destructive">
                    {fileRejections.map(({ file, errors }) => (
                        <li key={file.name}>
                            {file.name}: {errors[0]?.message}
                        </li>
                    ))}
                </ul>
            )}

            {(queue.length > 0 || ru.items.length > 0) && (
                <ul className="space-y-2">
                    {queue.map((item) => (
                        <ProgressRow
                            key={item.key}
                            name={item.name}
                            progress={item.progress}
                            error={item.error}
                            controls={
                                item.error ? (
                                    <IconButton
                                        label="Dismiss"
                                        onClick={() => remove(item.key)}
                                    >
                                        <X className="h-4 w-4" />
                                    </IconButton>
                                ) : null
                            }
                        />
                    ))}

                    {ru.items.map((item) => (
                        <ProgressRow
                            key={item.key}
                            name={item.name}
                            progress={item.progress}
                            label={STATUS_LABEL[item.status]}
                            error={
                                item.status === 'error'
                                    ? (item.error ?? 'Upload failed.')
                                    : undefined
                            }
                            controls={
                                <ResumableControls
                                    status={item.status}
                                    onPause={() => ru.pause(item.key)}
                                    onResume={() => ru.resume(item.key)}
                                    onRetry={() => ru.retry(item.key)}
                                    onDismiss={() => ru.dismiss(item.key)}
                                />
                            }
                        />
                    ))}
                </ul>
            )}
        </div>
    );
}

function ProgressRow({
    name,
    progress,
    error,
    label,
    controls,
}: {
    name: string;
    progress: number;
    error?: string;
    label?: string;
    controls?: ReactNode;
}) {
    return (
        <li className="rounded-md border px-3 py-2 text-sm">
            <div className="flex items-center justify-between gap-2">
                <span className="truncate">{name}</span>
                <div className="flex items-center gap-2">
                    {!error && (
                        <span className="text-xs text-muted-foreground">
                            {label ?? `${progress}%`}
                        </span>
                    )}
                    {controls}
                </div>
            </div>
            {error ? (
                <p className="mt-1 text-xs text-destructive">{error}</p>
            ) : (
                <div className="mt-1 h-1 w-full overflow-hidden rounded bg-muted">
                    <div
                        className="h-full bg-primary transition-all"
                        style={{ width: `${progress}%` }}
                    />
                </div>
            )}
        </li>
    );
}

function ResumableControls({
    status,
    onPause,
    onResume,
    onRetry,
    onDismiss,
}: {
    status: ResumableStatus;
    onPause: () => void;
    onResume: () => void;
    onRetry: () => void;
    onDismiss: () => void;
}) {
    const inFlight =
        status === 'uploading' ||
        status === 'preparing' ||
        status === 'resuming';

    return (
        <>
            {inFlight && (
                <IconButton label="Pause" onClick={onPause}>
                    <Pause className="h-4 w-4" />
                </IconButton>
            )}
            {status === 'paused' && (
                <IconButton label="Resume" onClick={onResume}>
                    <Play className="h-4 w-4" />
                </IconButton>
            )}
            {status === 'error' && (
                <IconButton label="Retry" onClick={onRetry}>
                    <RotateCw className="h-4 w-4" />
                </IconButton>
            )}
            {status !== 'done' && status !== 'assembling' && (
                <IconButton label="Cancel" onClick={onDismiss}>
                    <X className="h-4 w-4" />
                </IconButton>
            )}
        </>
    );
}

function IconButton({
    label,
    onClick,
    children,
}: {
    label: string;
    onClick: () => void;
    children: ReactNode;
}) {
    return (
        <button
            type="button"
            onClick={onClick}
            aria-label={label}
            className="text-muted-foreground hover:text-foreground"
        >
            {children}
        </button>
    );
}
