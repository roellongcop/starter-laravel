import { Badge } from '@/Components/ui/badge';
import { cn } from '@/lib/utils';
import { type TagChip } from '@/types';

interface Props {
    tags: TagChip[];
    className?: string;
}

/**
 * Renders a resource's attached DataTags as coloured chips (a swatch dot from
 * the tag's palette colour + its name). Returns nothing when there are no tags.
 */
export default function TagBadges({ tags, className }: Props) {
    if (tags.length === 0) {
        return null;
    }

    return (
        <div className={cn('flex flex-wrap gap-1.5', className)}>
            {tags.map((tag) => (
                <Badge
                    key={tag.token}
                    variant="outline"
                    className="gap-1.5 font-normal"
                >
                    <span
                        className="h-2 w-2 shrink-0 rounded-full"
                        style={{ backgroundColor: tag.color }}
                        aria-hidden
                    />
                    {tag.name}
                </Badge>
            ))}
        </div>
    );
}
