import axios from 'axios';
import { FileUp, X } from 'lucide-react';
import { useCallback, useState } from 'react';
import { type Accept, useDropzone } from 'react-dropzone';

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
}

const DEFAULT_ACCEPT: Accept = {
    'application/pdf': ['.pdf'],
    'application/msword': ['.doc'],
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
        '.docx',
    ],
};

/**
 * Reusable drag-and-drop uploader. Each accepted file is POSTed to `uploadUrl`
 * as multipart (`field`) with live progress; on success the returned JSON is
 * handed to `onUploaded`. Point `uploadUrl`/`accept` at any endpoint to reuse on
 * other pages.
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
}: Props) {
    const [queue, setQueue] = useState<UploadItem[]>([]);

    const update = (key: string, patch: Partial<UploadItem>) =>
        setQueue((q) => q.map((i) => (i.key === key ? { ...i, ...patch } : i)));

    const remove = (key: string) =>
        setQueue((q) => q.filter((i) => i.key !== key));

    const upload = useCallback(
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
        (accepted: File[]) => accepted.forEach(upload),
        [upload],
    );

    const { getRootProps, getInputProps, isDragActive, fileRejections } =
        useDropzone({
            onDrop,
            accept,
            multiple,
            maxSize: maxSizeMb * 1024 * 1024,
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

            {queue.length > 0 && (
                <ul className="space-y-2">
                    {queue.map((item) => (
                        <li
                            key={item.key}
                            className="rounded-md border px-3 py-2 text-sm"
                        >
                            <div className="flex items-center justify-between gap-2">
                                <span className="truncate">{item.name}</span>
                                {item.error ? (
                                    <button
                                        type="button"
                                        onClick={() => remove(item.key)}
                                        className="text-muted-foreground hover:text-foreground"
                                        aria-label="Dismiss"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                ) : (
                                    <span className="text-xs text-muted-foreground">
                                        {item.progress}%
                                    </span>
                                )}
                            </div>
                            {item.error ? (
                                <p className="mt-1 text-xs text-destructive">
                                    {item.error}
                                </p>
                            ) : (
                                <div className="mt-1 h-1 w-full overflow-hidden rounded bg-muted">
                                    <div
                                        className="h-full bg-primary transition-all"
                                        style={{ width: `${item.progress}%` }}
                                    />
                                </div>
                            )}
                        </li>
                    ))}
                </ul>
            )}
        </div>
    );
}
