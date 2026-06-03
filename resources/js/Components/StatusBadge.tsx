import { Badge } from '@/Components/ui/badge';

const VARIANT: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    Generated: 'default',
    Restored: 'default',
    Done: 'default',
    Failed: 'destructive',
    RestoreFailed: 'destructive',
    Pending: 'secondary',
    Generating: 'secondary',
    Restoring: 'secondary',
    Running: 'secondary',
};

/** Maps a lifecycle status enum value to a coloured badge. */
export default function StatusBadge({ status }: { status: string }) {
    return <Badge variant={VARIANT[status] ?? 'outline'}>{status}</Badge>;
}
