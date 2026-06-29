import { useSortable } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    Clock,
    GripVertical,
    Lock,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Trash2,
    User as UserIcon,
} from 'lucide-react';

import Can from '@/Components/Can';
import TagBadgesRow from '@/Components/TagBadgesRow';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { type AdminTask } from '@/types';

interface Props {
    task: AdminTask;
    canManage: boolean;
    onEdit: () => void;
    onDelete: () => void;
}

export default function TaskCard({ task, canManage, onEdit, onDelete }: Props) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: task.token,
        disabled: !canManage,
        data: { type: 'task', columnId: task.milestone },
    });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`rounded-md border bg-background p-2.5 text-sm shadow-sm ${
                isDragging ? 'opacity-50' : ''
            }`}
        >
            <div className="flex items-start gap-1.5">
                {canManage && (
                    <button
                        type="button"
                        className="mt-0.5 cursor-grab text-muted-foreground"
                        aria-label="Drag task"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="h-4 w-4" />
                    </button>
                )}
                <p className="min-w-0 flex-1 font-medium leading-snug">
                    {task.name}
                </p>
                {canManage && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="h-6 w-6 shrink-0"
                                aria-label="Task actions"
                            >
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <Can ability="tasks.update">
                                <DropdownMenuItem onClick={onEdit}>
                                    <Pencil className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            </Can>
                            <Can ability="tasks.delete">
                                <DropdownMenuItem
                                    onClick={onDelete}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Delete
                                </DropdownMenuItem>
                            </Can>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>

            {task.description && (
                <p className="mt-1 line-clamp-2 text-xs text-muted-foreground">
                    {task.description}
                </p>
            )}

            <div className="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                {task.assigned_to && (
                    <span className="flex items-center gap-1">
                        <UserIcon className="h-3.5 w-3.5" />
                        {task.assigned_to.name}
                    </span>
                )}
                {task.due_date && (
                    <span className="flex items-center gap-1">
                        <Clock className="h-3.5 w-3.5" />
                        {task.due_date}
                    </span>
                )}
                {task.private && (
                    <span className="flex items-center gap-1">
                        <Lock className="h-3.5 w-3.5" />
                        Private
                    </span>
                )}
                {task.reference_file && (
                    <span className="flex min-w-0 items-center gap-1">
                        <Paperclip className="h-3.5 w-3.5 shrink-0" />
                        <span className="truncate">
                            {task.reference_file.name}
                        </span>
                    </span>
                )}
            </div>

            {task.tags.length > 0 && (
                <TagBadgesRow tags={task.tags} className="mt-2" />
            )}
        </div>
    );
}
