import {
    SortableContext,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    ChevronDown,
    ChevronRight,
    GripVertical,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';

import Can from '@/Components/Can';
import { Button } from '@/Components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import { type AdminMilestone, type AdminTask } from '@/types';
import TaskCard from './TaskCard';

interface Props {
    milestone: AdminMilestone;
    canManage: boolean;
    collapsed: boolean;
    onToggleCollapse: () => void;
    onAddTask: () => void;
    onEditMilestone: () => void;
    onDeleteMilestone: () => void;
    onEditTask: (task: AdminTask) => void;
    onDeleteTask: (task: AdminTask) => void;
}

export default function MilestoneColumn({
    milestone,
    canManage,
    collapsed,
    onToggleCollapse,
    onAddTask,
    onEditMilestone,
    onDeleteMilestone,
    onEditTask,
    onDeleteTask,
}: Props) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({
        id: milestone.token,
        disabled: !canManage,
        data: { type: 'column' },
    });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`flex w-full flex-col rounded-lg border bg-muted/30 ${
                isDragging ? 'opacity-60' : ''
            }`}
        >
            <div className="flex items-start gap-1.5 border-b p-2.5">
                {canManage && (
                    <button
                        type="button"
                        className="mt-0.5 cursor-grab text-muted-foreground"
                        aria-label="Drag column"
                        {...attributes}
                        {...listeners}
                    >
                        <GripVertical className="h-4 w-4" />
                    </button>
                )}
                <button
                    type="button"
                    onClick={onToggleCollapse}
                    aria-label={
                        collapsed ? 'Expand milestone' : 'Collapse milestone'
                    }
                    aria-expanded={!collapsed}
                    className="mt-0.5 text-muted-foreground hover:text-foreground"
                >
                    {collapsed ? (
                        <ChevronRight className="h-4 w-4" />
                    ) : (
                        <ChevronDown className="h-4 w-4" />
                    )}
                </button>
                <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold">{milestone.name}</p>
                    {!collapsed && milestone.description && (
                        <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                            {milestone.description}
                        </p>
                    )}
                </div>
                <span className="shrink-0 rounded bg-muted px-1.5 py-0.5 text-xs text-muted-foreground">
                    {milestone.tasks.length}
                </span>
                {canManage && !milestone.is_default && (
                    <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                            <Button
                                size="icon"
                                variant="ghost"
                                className="h-6 w-6 shrink-0"
                                aria-label="Milestone actions"
                            >
                                <MoreHorizontal className="h-4 w-4" />
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                            <Can ability="milestones.update">
                                <DropdownMenuItem onClick={onEditMilestone}>
                                    <Pencil className="mr-2 h-4 w-4" /> Edit
                                </DropdownMenuItem>
                            </Can>
                            <Can ability="milestones.delete">
                                <DropdownMenuItem
                                    onClick={onDeleteMilestone}
                                    className="text-destructive focus:text-destructive"
                                >
                                    <Trash2 className="mr-2 h-4 w-4" /> Delete
                                </DropdownMenuItem>
                            </Can>
                        </DropdownMenuContent>
                    </DropdownMenu>
                )}
            </div>

            {!collapsed && (
                <>
                    <div className="flex-1 space-y-2 p-2">
                        <SortableContext
                            items={milestone.tasks.map((task) => task.token)}
                            strategy={verticalListSortingStrategy}
                        >
                            {milestone.tasks.length === 0 ? (
                                <p className="rounded border border-dashed px-3 py-6 text-center text-xs text-muted-foreground">
                                    No tasks
                                </p>
                            ) : (
                                milestone.tasks.map((task) => (
                                    <TaskCard
                                        key={task.token}
                                        task={task}
                                        canManage={canManage}
                                        onEdit={() => onEditTask(task)}
                                        onDelete={() => onDeleteTask(task)}
                                    />
                                ))
                            )}
                        </SortableContext>
                    </div>

                    <Can ability="tasks.create">
                        <div className="p-2 pt-0">
                            <Button
                                variant="ghost"
                                size="sm"
                                className="w-full justify-start text-muted-foreground"
                                onClick={onAddTask}
                            >
                                <Plus className="mr-1 h-4 w-4" /> Add task
                            </Button>
                        </div>
                    </Can>
                </>
            )}
        </div>
    );
}
