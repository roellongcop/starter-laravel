import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Link } from '@inertiajs/react';
import axios from 'axios';
import {
    Clock,
    Globe,
    GripVertical,
    ListChecks,
    Lock,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Trash2,
    User as UserIcon,
    Users as UsersIcon,
} from 'lucide-react';

import Can from '@/Components/Can';
import StatusBadge from '@/Components/StatusBadge';
import StatusDropdown from '@/Components/StatusDropdown';
import TagEditor from '@/Components/TagEditor';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { usePermissions } from '@/lib/permissions';
import { type AdminTask, type SelectOption } from '@/types';
import { memo } from 'react';

interface Props {
    task: AdminTask;
    canManage: boolean;
    /** Suppresses drag (grip + sortable) — e.g. while a filter is active. */
    dragDisabled?: boolean;
    projectToken: string;
    assetToken: string;
    /** The board's organization token — scopes the inline tag editor. */
    assetOrganization: string | null;
    taskStatusOptions: SelectOption[];
    // Stable, task-taking callbacks (not per-card closures) so React.memo holds.
    onEdit: (task: AdminTask) => void;
    onDelete: (task: AdminTask) => void;
}

function TaskCard({
    task,
    canManage,
    dragDisabled = false,
    projectToken,
    assetToken,
    assetOrganization,
    taskStatusOptions,
    onEdit,
    onDelete,
}: Props) {
    const { can } = usePermissions();
    const canDrag = canManage && !dragDisabled;
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: task.token,
        disabled: !canDrag,
        data: { type: 'task', columnId: task.milestone },
    });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            // A shared minimum height keeps cards uniform; the name/description are
            // clamped to one line and the meta + tags sit at the bottom (mt-auto).
            className={`relative flex min-h-[7rem] items-stretch overflow-hidden rounded-md border bg-background text-sm shadow-sm transition-all hover:border-ring hover:shadow-md ${
                isDragging ? 'opacity-50' : ''
            }`}
        >
            {/* Status as a leading, fully-clickable "addon" cell (matches project/asset cards). */}
            <div className="flex items-stretch border-r bg-muted/30">
                <Can
                    ability="tasks.update"
                    fallback={
                        <span className="flex items-center px-3">
                            <StatusBadge status={task.status} />
                        </span>
                    }
                >
                    <StatusDropdown
                        iconOnly
                        variant="ghost"
                        className="h-full w-auto rounded-none px-3"
                        value={task.status}
                        options={taskStatusOptions}
                        onSelect={(status) =>
                            axios.patch(
                                route('projects.assets.tasks.status', [
                                    projectToken,
                                    assetToken,
                                    task.token,
                                ]),
                                { status },
                            )
                        }
                    />
                </Can>
            </div>

            <div className="flex min-w-0 flex-1 flex-col p-2.5">
                <div className="flex items-start gap-1.5">
                    {canDrag && (
                        <button
                            type="button"
                            className="relative z-10 mt-0.5 cursor-grab text-muted-foreground"
                            aria-label="Drag task"
                            {...attributes}
                            {...listeners}
                        >
                            <GripVertical className="h-4 w-4" />
                        </button>
                    )}
                    {/* Stretched link: clicking anywhere on the card opens the task. */}
                    <Link
                        href={route('projects.assets.tasks.show', [
                            projectToken,
                            assetToken,
                            task.token,
                        ])}
                        title={task.name}
                        className="line-clamp-1 min-w-0 flex-1 font-medium leading-snug after:absolute after:inset-0 focus-visible:outline-none"
                    >
                        {task.name}
                    </Link>
                    {canManage && (
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    className="relative z-10 h-6 w-6 shrink-0"
                                    aria-label="Task actions"
                                >
                                    <MoreHorizontal className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <Can ability="tasks.update">
                                    <DropdownMenuItem
                                        onClick={() => onEdit(task)}
                                    >
                                        <Pencil className="mr-2 h-4 w-4" /> Edit
                                    </DropdownMenuItem>
                                </Can>
                                <Can ability="tasks.delete">
                                    <DropdownMenuItem
                                        onClick={() => onDelete(task)}
                                        className="text-destructive focus:text-destructive"
                                    >
                                        <Trash2 className="mr-2 h-4 w-4" />{' '}
                                        Delete
                                    </DropdownMenuItem>
                                </Can>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    )}
                </div>

                {task.description ? (
                    <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                        {task.description}
                    </p>
                ) : (
                    <p className="mt-1 text-xs italic text-muted-foreground/70">
                        No description
                    </p>
                )}

                {/* Meta + tags anchored to the bottom so cards stay uniform height. */}
                <div className="mt-auto flex flex-col gap-1.5 pt-2">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                        {task.assigned_to && (
                            <span className="flex items-center gap-1">
                                {task.assigned_to.type === 'team' ? (
                                    <UsersIcon className="h-3.5 w-3.5" />
                                ) : (
                                    <UserIcon className="h-3.5 w-3.5" />
                                )}
                                {task.assigned_to.name}
                            </span>
                        )}
                        {task.due_date && (
                            <span className="flex items-center gap-1">
                                <Clock className="h-3.5 w-3.5" />
                                {task.due_date}
                            </span>
                        )}
                        <span className="flex items-center gap-1">
                            {task.private ? (
                                <Lock className="h-3.5 w-3.5" />
                            ) : (
                                <Globe className="h-3.5 w-3.5" />
                            )}
                            {task.private ? 'Private' : 'Public'}
                        </span>
                        {task.reference_file && (
                            <span className="flex min-w-0 items-center gap-1">
                                <Paperclip className="h-3.5 w-3.5 shrink-0" />
                                <span className="truncate">
                                    {task.reference_file.name}
                                </span>
                            </span>
                        )}
                        {task.requirements_count > 0 && (
                            <span
                                className="flex items-center gap-1"
                                title={`${task.requirements_count} requirement(s)`}
                            >
                                <ListChecks className="h-3.5 w-3.5" />
                                {task.requirements_count}
                            </span>
                        )}
                    </div>

                    <TagEditor
                        tags={task.tags}
                        organization={assetOrganization}
                        type="tasks"
                        token={task.token}
                        canEdit={can('tasks.update')}
                        singleRow
                    />
                </div>
            </div>
        </div>
    );
}

// Memoized: on a large board (hundreds of cards) this skips re-rendering every
// card on each filter keystroke / drag when its own props are unchanged.
export default memo(TaskCard);
