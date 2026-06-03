import { Head, Link } from '@inertiajs/react';

import PageHeader from '@/Components/PageHeader';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/Components/ui/table';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { type AdminVisitLog, type AdminVisitor } from '@/types';

interface Props {
    visitor: AdminVisitor;
    logs: AdminVisitLog[];
}

export default function Show({ visitor, logs }: Props) {
    return (
        <AuthenticatedLayout header="Visitor">
            <Head title="Visitor" />
            <PageHeader
                title={visitor.ip_address ?? visitor.cookie_id}
                description={`${visitor.browser} / ${visitor.os} · ${visitor.visit_count} visits`}
                actions={
                    <Button variant="outline" asChild>
                        <Link href={route('visitors.index')}>Back</Link>
                    </Button>
                }
            />

            <Card>
                <CardHeader>
                    <CardTitle>Recent activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Action</TableHead>
                                <TableHead>URL</TableHead>
                                <TableHead>When</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {logs.length === 0 && (
                                <TableRow>
                                    <TableCell
                                        colSpan={3}
                                        className="text-center text-muted-foreground"
                                    >
                                        No activity.
                                    </TableCell>
                                </TableRow>
                            )}
                            {logs.map((l) => (
                                <TableRow key={l.id}>
                                    <TableCell>
                                        <Badge variant="secondary">
                                            {l.action}
                                        </Badge>
                                    </TableCell>
                                    <TableCell className="break-all text-sm">
                                        {l.url}
                                    </TableCell>
                                    <TableCell className="text-sm text-muted-foreground">
                                        {l.created_at
                                            ? new Date(
                                                  l.created_at,
                                              ).toLocaleString()
                                            : '—'}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </AuthenticatedLayout>
    );
}
