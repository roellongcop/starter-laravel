import { Download, Trash2 } from 'lucide-react';
import { useState } from 'react';

import CursorPager from '@/Components/CursorPager';
import FileViewer, { type ViewerFile } from '@/Components/FileViewer';
import { Button } from '@/Components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import { humanSize } from '@/lib/format';
import { type AdminDocument, type CursorResponse } from '@/types';

interface Props {
    documents: CursorResponse<AdminDocument>;
    /** When provided, a delete button is shown that calls this with the doc. */
    onDelete?: (doc: AdminDocument) => void;
    emptyText?: string;
    /** Distinct query param so a list can paginate without clashing. */
    cursorName?: string;
}

/** Reusable table of documents with download (and optional delete) + pager. */
export default function DocumentList({
    documents,
    onDelete,
    emptyText = 'No documents uploaded yet.',
    cursorName,
}: Props) {
    const [viewing, setViewing] = useState<ViewerFile | null>(null);

    if (documents.data.length === 0) {
        return <p className="text-sm text-muted-foreground">{emptyText}</p>;
    }

    const preview = (doc: AdminDocument) =>
        setViewing({
            name: doc.name,
            extension: doc.extension,
            url: route('documents.view', doc.token),
            downloadUrl: doc.url,
        });

    return (
        <div className="space-y-4">
            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Uploaded</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {documents.data.map((doc) => (
                            <TableRow key={doc.token}>
                                <TableCell className="font-medium">
                                    <button
                                        type="button"
                                        onClick={() => preview(doc)}
                                        className="text-left hover:underline"
                                    >
                                        {doc.name}
                                    </button>
                                </TableCell>
                                <TableCell className="uppercase text-muted-foreground">
                                    {doc.extension}
                                </TableCell>
                                <TableCell>{humanSize(doc.size)}</TableCell>
                                <TableCell className="text-muted-foreground">
                                    {doc.created_at
                                        ? new Date(
                                              doc.created_at,
                                          ).toLocaleDateString()
                                        : '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            asChild
                                        >
                                            <a href={doc.url} title="Download">
                                                <Download className="h-4 w-4" />
                                            </a>
                                        </Button>
                                        {onDelete && (
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                title="Delete"
                                                aria-label="Delete"
                                                onClick={() => onDelete(doc)}
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <CursorPager
                nextCursor={documents.next_cursor}
                prevCursor={documents.prev_cursor}
                cursorName={cursorName}
            />

            <FileViewer file={viewing} onClose={() => setViewing(null)} />
        </div>
    );
}
