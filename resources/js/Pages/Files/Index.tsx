import { Head, Link, router } from '@inertiajs/react';
import { Download, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import CursorPager from '@/Components/CursorPager';
import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminFile, type CursorResponse } from '@/types';

interface Props {
    files: CursorResponse<AdminFile>;
    filters: { search: string };
    can: { create: boolean; delete: boolean };
}

function humanSize(bytes: number): string {
    if (bytes < 1024) return `${bytes} B`;
    const units = ['KB', 'MB', 'GB'];
    let n = bytes / 1024;
    let i = 0;
    while (n >= 1024 && i < units.length - 1) {
        n /= 1024;
        i++;
    }
    return `${n.toFixed(1)} ${units[i]}`;
}

export default function Index({ files, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    const [deleting, setDeleting] = useState<AdminFile | null>(null);

    const submitSearch: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('files.index'),
            { search },
            { preserveState: true, preserveScroll: true, replace: true },
        );
    };

    const destroy = () => {
        if (!deleting) return;
        router.delete(route('files.destroy', deleting.id), {
            preserveScroll: true,
            onFinish: () => setDeleting(null),
        });
    };

    return (
        <AuthenticatedLayout header="Files">
            <Head title="Files" />

            <PageHeader
                title="Files"
                description="Uploads stored on a private disk; downloads go through gated links."
                actions={
                    <Can ability="files.create">
                        <Button asChild>
                            <Link href={route('files.create')}>
                                <Plus className="h-4 w-4" /> Upload
                            </Link>
                        </Button>
                    </Can>
                }
            />

            <form onSubmit={submitSearch} className="mb-4 flex gap-2">
                <Input
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    placeholder="Search name or tag…"
                    className="w-72"
                />
                <Button type="submit" variant="secondary">
                    Search
                </Button>
            </form>

            <div className="rounded-lg border">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Size</TableHead>
                            <TableHead>Tag</TableHead>
                            <TableHead>Owner</TableHead>
                            <TableHead className="text-right">
                                Actions
                            </TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {files.data.length === 0 && (
                            <TableRow>
                                <TableCell
                                    colSpan={6}
                                    className="text-center text-muted-foreground"
                                >
                                    No files found.
                                </TableCell>
                            </TableRow>
                        )}
                        {files.data.map((file) => (
                            <TableRow key={file.id}>
                                <TableCell className="font-medium">
                                    <Link
                                        href={route('files.show', file.id)}
                                        className="hover:underline"
                                    >
                                        {file.original_name}
                                    </Link>
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {file.extension ?? file.mime ?? '—'}
                                </TableCell>
                                <TableCell>{humanSize(file.size)}</TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {file.tag ?? '—'}
                                </TableCell>
                                <TableCell className="text-sm text-muted-foreground">
                                    {file.owner ?? '—'}
                                </TableCell>
                                <TableCell className="text-right">
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            size="icon"
                                            variant="ghost"
                                            asChild
                                        >
                                            <a
                                                href={route(
                                                    'files.download',
                                                    file.id,
                                                )}
                                            >
                                                <Download className="h-4 w-4" />
                                            </a>
                                        </Button>
                                        <Can ability="files.delete">
                                            <Button
                                                size="icon"
                                                variant="ghost"
                                                onClick={() =>
                                                    setDeleting(file)
                                                }
                                            >
                                                <Trash2 className="h-4 w-4 text-destructive" />
                                            </Button>
                                        </Can>
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <div className="mt-4">
                <CursorPager
                    nextCursor={files.next_cursor}
                    prevCursor={files.prev_cursor}
                />
            </div>

            <ConfirmDialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
                title={`Delete ${deleting?.original_name}?`}
                description="This permanently removes the file."
                confirmLabel="Delete"
                destructive
                onConfirm={destroy}
            />
        </AuthenticatedLayout>
    );
}
