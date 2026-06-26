import { Badge } from '@/Components/ui/badge';

const VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Generated: 'default',
    Restored: 'default',
    Done: 'default',
    Approved: 'default',
    Failed: 'destructive',
    RestoreFailed: 'destructive',
    Cancelled: 'destructive',
    Pending: 'secondary',
    Generating: 'secondary',
    Restoring: 'secondary',
    Running: 'secondary',
    'In Progress': 'secondary',
};

/** Maps a lifecycle status enum value to a coloured badge. */
export default function StatusBadge({ status }: { status: string }) {
    return <Badge variant={VARIANT[status] ?? 'outline'}>{status}</Badge>;
}
