import axios from 'axios';
import { Download, FileText } from 'lucide-react';
import { useEffect, useState } from 'react';

import { Button } from '@/Components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/Components/ui/dialog';

export interface ViewerFile {
    name: string;
    extension?: string | null;
    mime?: string | null;
    /** Inline stream URL (Content-Disposition: inline). */
    url: string;
    /** Attachment URL for the download button (defaults to `url`). */
    downloadUrl?: string;
}

interface Props {
    file: ViewerFile | null;
    onClose: () => void;
}

const IMAGE_EXT = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'bmp', 'avif'];

type Kind = 'image' | 'pdf' | 'sheet' | 'docx' | 'other';

function kindOf(ext?: string | null, mime?: string | null): Kind {
    const e = (ext ?? '').toLowerCase();
    if (IMAGE_EXT.includes(e) || mime?.startsWith('image/')) return 'image';
    if (e === 'pdf' || mime === 'application/pdf') return 'pdf';
    if (['csv', 'xls', 'xlsx'].includes(e)) return 'sheet';
    if (e === 'docx') return 'docx';
    return 'other';
}

/**
 * In-app file preview: image/pdf native, csv/xls/xlsx (SheetJS) and docx (mammoth)
 * parsed client-side via lazy imports. See docs/features/files-and-media.md.
 */
export default function FileViewer({ file, onClose }: Props) {
    const kind = file ? kindOf(file.extension, file.mime) : 'other';

    return (
        <Dialog open={file !== null} onOpenChange={(o) => !o && onClose()}>
            <DialogContent className="max-h-[90vh] overflow-hidden sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle className="truncate">{file?.name}</DialogTitle>
                </DialogHeader>

                {file && (
                    <div className="max-h-[72vh] overflow-auto">
                        {kind === 'image' && (
                            <img
                                src={file.url}
                                alt={file.name}
                                className="mx-auto max-h-[70vh] object-contain"
                            />
                        )}
                        {kind === 'pdf' && (
                            <iframe
                                src={file.url}
                                title={file.name}
                                className="h-[72vh] w-full rounded border"
                            />
                        )}
                        {kind === 'sheet' && <SheetPreview url={file.url} />}
                        {kind === 'docx' && <DocxPreview url={file.url} />}
                        {kind === 'other' && (
                            <Unsupported
                                name={file.name}
                                href={file.downloadUrl ?? file.url}
                            />
                        )}
                    </div>
                )}

                <DialogFooter>
                    {file && (
                        <Button variant="outline" asChild>
                            <a href={file.downloadUrl ?? file.url}>
                                <Download className="mr-1 h-4 w-4" /> Download
                            </a>
                        </Button>
                    )}
                    <Button onClick={onClose}>Close</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function Loading() {
    return (
        <p className="py-12 text-center text-sm text-muted-foreground">
            Loading preview…
        </p>
    );
}

function Unsupported({ name, href }: { name: string; href: string }) {
    return (
        <div className="flex flex-col items-center gap-3 py-12 text-center">
            <FileText className="h-10 w-10 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">
                No in-app preview for this file type.
            </p>
            <Button variant="outline" asChild>
                <a href={href}>
                    <Download className="mr-1 h-4 w-4" /> Download {name}
                </a>
            </Button>
        </div>
    );
}

function SheetPreview({ url }: { url: string }) {
    const [sheets, setSheets] = useState<
        { name: string; rows: string[][] }[] | null
    >(null);
    const [active, setActive] = useState(0);
    const [error, setError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const [{ data }, XLSX] = await Promise.all([
                    axios.get(url, { responseType: 'arraybuffer' }),
                    import('xlsx'),
                ]);
                const wb = XLSX.read(new Uint8Array(data), { type: 'array' });
                const parsed = wb.SheetNames.map((name) => ({
                    name,
                    rows: XLSX.utils
                        .sheet_to_json<string[]>(wb.Sheets[name], {
                            header: 1,
                            blankrows: false,
                            defval: '',
                        })
                        .slice(0, 500),
                }));
                if (!cancelled) setSheets(parsed);
            } catch {
                if (!cancelled) setError(true);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [url]);

    if (error)
        return (
            <p className="py-12 text-center text-sm text-destructive">
                Could not read this spreadsheet.
            </p>
        );
    if (!sheets) return <Loading />;

    const sheet = sheets[active];

    return (
        <div className="space-y-3">
            {sheets.length > 1 && (
                <div className="flex flex-wrap gap-1">
                    {sheets.map((s, i) => (
                        <Button
                            key={s.name}
                            size="sm"
                            variant={i === active ? 'default' : 'outline'}
                            onClick={() => setActive(i)}
                        >
                            {s.name}
                        </Button>
                    ))}
                </div>
            )}
            <div className="overflow-auto rounded border">
                <table className="w-full border-collapse text-sm">
                    <tbody>
                        {sheet.rows.map((row, r) => (
                            <tr key={r} className="even:bg-muted/40">
                                {row.map((cell, c) => (
                                    <td
                                        key={c}
                                        className="whitespace-nowrap border px-2 py-1"
                                    >
                                        {String(cell)}
                                    </td>
                                ))}
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function DocxPreview({ url }: { url: string }) {
    const [html, setHtml] = useState<string | null>(null);
    const [error, setError] = useState(false);

    useEffect(() => {
        let cancelled = false;
        (async () => {
            try {
                const { data } = await axios.get(url, {
                    responseType: 'arraybuffer',
                });
                // Browser build avoids node-only deps.
                const mammoth = await import('mammoth/mammoth.browser');
                const result = await mammoth.convertToHtml({
                    arrayBuffer: data,
                });
                if (!cancelled) setHtml(result.value);
            } catch {
                if (!cancelled) setError(true);
            }
        })();
        return () => {
            cancelled = true;
        };
    }, [url]);

    if (error)
        return (
            <p className="py-12 text-center text-sm text-destructive">
                Could not render this document.
            </p>
        );
    if (html === null) return <Loading />;

    return (
        <div
            className="prose prose-sm dark:prose-invert max-w-none"
            dangerouslySetInnerHTML={{ __html: html }}
        />
    );
}
