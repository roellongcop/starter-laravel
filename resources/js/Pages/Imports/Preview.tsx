import { Head, router } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Button } from '@/Components/ui/button';
import { Card, CardContent } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminImport } from '@/types';

interface Props {
    import: AdminImport;
    headings: string[];
    rows: Record<string, unknown>[];
}

export default function Preview({ import: imp, headings, rows }: Props) {
    const process = () => router.post(route('imports.process', imp.token));

    return (
        <AuthenticatedLayout>
            <Head title="Import Preview" />
            <PageHeader
                title="Import Preview"
                description="Review the first rows, then process — the full file is counted in the background."
                actions={
                    <>
                        <Button
                            variant="outline"
                            onClick={() =>
                                router.delete(
                                    route('imports.destroy', imp.token),
                                )
                            }
                        >
                            Cancel
                        </Button>
                        <Button onClick={process}>Process import</Button>
                    </>
                }
            />

            <Card>
                <CardContent className="overflow-auto pt-6">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                {headings.map((h) => (
                                    <TableHead key={h}>{h}</TableHead>
                                ))}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {rows.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={Math.max(headings.length, 1)}
                                        className="text-center text-muted-foreground"
                                    >
                                        No rows found in the file.
                                    </TableCell>
                                </TableRow>
                            )}
                            {rows.map((row, i) => (
                                <TableRow key={i}>
                                    {headings.map((h) => (
                                        <TableCell key={h} className="text-sm">
                                            {String(row[h] ?? '')}
                                        </TableCell>
                                    ))}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
