import {
    closestCorners,
    DndContext,
    type DragEndEvent,
    type DragOverEvent,
    DragOverlay,
    type DragStartEvent,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { router } from '@inertiajs/react';
import axios from 'axios';
import { Plus } from 'lucide-react';
import { useEffect, useMemo, useRef, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import { toast } from '@/hooks/use-toast';
import {
    type AdminMilestone,
    type AdminTask,
    type DataTagOption,
    type SelectOption,
} from '@/types';
import MilestoneColumn from './MilestoneColumn';
import MilestoneFormSheet from './MilestoneFormSheet';
import TaskCard from './TaskCard';
import TaskFormSheet from './TaskFormSheet';

interface Props {
    projectToken: string;
    assetToken: string;
    assetOrganization: string | null;
    milestones: AdminMilestone[];
    canManage: boolean;
    userOptions: SelectOption[];
    referenceFileOptions: SelectOption[];
    dataTags: DataTagOption[];
}

const columnOf = (taskToken: string, cols: AdminMilestone[]): string | null =>
    cols.find((c) => c.tasks.some((t) => t.token === taskToken))?.token ?? null;

export default function MilestoneBoard({
    projectToken,
    assetToken,
    assetOrganization,
    milestones,
    canManage,
    userOptions,
    referenceFileOptions,
    dataTags,
}: Props) {
    const [columns, setColumns] = useState<AdminMilestone[]>(milestones);
    const [activeId, setActiveId] = useState<string | null>(null);

    // Mirror the latest committed board so drag-end can persist the post-drag
    // state without a stale closure (the over/end events span renders).
    const columnsRef = useRef(columns);
    columnsRef.current = columns;

    // Resync when the server sends fresh data (after a CRUD reload). Drag reorders
    // persist via axios and never touch props, so they don't trigger a resync.
    useEffect(() => setColumns(milestones), [milestones]);

    const [milestoneSheetOpen, setMilestoneSheetOpen] = useState(false);
    const [milestoneEditing, setMilestoneEditing] =
        useState<AdminMilestone | null>(null);
    const [taskSheetOpen, setTaskSheetOpen] = useState(false);
    const [taskEditing, setTaskEditing] = useState<AdminTask | null>(null);
    const [taskDefaultMilestone, setTaskDefaultMilestone] = useState<
        string | undefined
    >(undefined);
    const [milestoneToDelete, setMilestoneToDelete] =
        useState<AdminMilestone | null>(null);
    const [taskToDelete, setTaskToDelete] = useState<AdminTask | null>(null);

    const availableTags = useMemo(
        () => dataTags.filter((t) => t.organization === assetOrganization),
        [dataTags, assetOrganization],
    );

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const persist = (cols: AdminMilestone[]) => {
        axios
            .patch(
                route('projects.assets.reorder', [projectToken, assetToken]),
                {
                    milestones: cols.map((c) => c.token),
                    tasks: Object.fromEntries(
                        cols.map((c) => [c.token, c.tasks.map((t) => t.token)]),
                    ),
                },
            )
            .catch(() => {
                toast({
                    variant: 'destructive',
                    description: 'Could not save the new order.',
                });
                router.reload({ only: ['milestones'] });
            });
    };

    const onDragStart = (e: DragStartEvent) => setActiveId(String(e.active.id));

    // Smoothly move a card into another column mid-drag (final order is committed
    // on drag end). Operates on the freshest state via the functional updater.
    const onDragOver = (e: DragOverEvent) => {
        const { active, over } = e;
        if (!over || active.data.current?.type !== 'task') {
            return;
        }

        setColumns((prev) => {
            const activeCol = columnOf(String(active.id), prev);
            const overCol =
                over.data.current?.type === 'column'
                    ? String(over.id)
                    : columnOf(String(over.id), prev);
            if (!activeCol || !overCol || activeCol === overCol) {
                return prev;
            }

            const next = prev.map((c) => ({ ...c, tasks: [...c.tasks] }));
            const from = next.find((c) => c.token === activeCol);
            const to = next.find((c) => c.token === overCol);
            if (!from || !to) {
                return prev;
            }
            const idx = from.tasks.findIndex((t) => t.token === active.id);
            if (idx === -1) {
                return prev;
            }
            const [moving] = from.tasks.splice(idx, 1);
            moving.milestone = overCol;
            const overIdx = to.tasks.findIndex((t) => t.token === over.id);
            to.tasks.splice(
                overIdx >= 0 ? overIdx : to.tasks.length,
                0,
                moving,
            );
            return next;
        });
    };

    const onDragEnd = (e: DragEndEvent) => {
        const { active, over } = e;
        setActiveId(null);
        if (!over) {
            return;
        }

        const cols = columnsRef.current;
        const type = active.data.current?.type;

        if (type === 'column') {
            const overCol =
                over.data.current?.type === 'column'
                    ? String(over.id)
                    : columnOf(String(over.id), cols);
            const from = cols.findIndex((c) => c.token === active.id);
            const to = cols.findIndex((c) => c.token === overCol);
            if (from !== -1 && to !== -1 && from !== to) {
                const next = arrayMove(cols, from, to);
                setColumns(next);
                persist(next);
            }
            return;
        }

        // Task: reorder within its (possibly newly-moved) column.
        const activeCol = columnOf(String(active.id), cols);
        const overCol =
            over.data.current?.type === 'column'
                ? String(over.id)
                : columnOf(String(over.id), cols);

        if (
            activeCol &&
            overCol &&
            activeCol === overCol &&
            active.id !== over.id
        ) {
            const col = cols.find((c) => c.token === activeCol);
            if (col) {
                const oldIdx = col.tasks.findIndex(
                    (t) => t.token === active.id,
                );
                const newIdx = col.tasks.findIndex((t) => t.token === over.id);
                if (oldIdx !== -1 && newIdx !== -1 && oldIdx !== newIdx) {
                    const next = cols.map((c) =>
                        c.token === activeCol
                            ? {
                                  ...c,
                                  tasks: arrayMove(c.tasks, oldIdx, newIdx),
                              }
                            : c,
                    );
                    setColumns(next);
                    persist(next);
                    return;
                }
            }
        }

        // A cross-column move was already applied in onDragOver — persist it.
        persist(cols);
    };

    const activeTask = activeId
        ? (columns.flatMap((c) => c.tasks).find((t) => t.token === activeId) ??
          null)
        : null;
    const activeColumn =
        activeId && !activeTask
            ? (columns.find((c) => c.token === activeId) ?? null)
            : null;

    const openCreateMilestone = () => {
        setMilestoneEditing(null);
        setMilestoneSheetOpen(true);
    };
    const openEditMilestone = (m: AdminMilestone) => {
        setMilestoneEditing(m);
        setMilestoneSheetOpen(true);
    };
    const openCreateTask = (milestoneToken: string) => {
        setTaskEditing(null);
        setTaskDefaultMilestone(milestoneToken);
        setTaskSheetOpen(true);
    };
    const openEditTask = (t: AdminTask) => {
        setTaskEditing(t);
        setTaskDefaultMilestone(undefined);
        setTaskSheetOpen(true);
    };

    const deleteMilestone = () => {
        if (!milestoneToDelete) {
            return;
        }
        router.delete(
            route('projects.assets.milestones.destroy', [
                projectToken,
                assetToken,
                milestoneToDelete.token,
            ]),
            {
                preserveScroll: true,
                onFinish: () => setMilestoneToDelete(null),
            },
        );
    };

    const deleteTask = () => {
        if (!taskToDelete) {
            return;
        }
        router.delete(
            route('projects.assets.tasks.destroy', [
                projectToken,
                assetToken,
                taskToDelete.token,
            ]),
            { preserveScroll: true, onFinish: () => setTaskToDelete(null) },
        );
    };

    return (
        <div>
            <div className="flex flex-col gap-4">
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCorners}
                    onDragStart={onDragStart}
                    onDragOver={onDragOver}
                    onDragEnd={onDragEnd}
                >
                    <SortableContext
                        items={columns.map((c) => c.token)}
                        strategy={verticalListSortingStrategy}
                    >
                        {columns.map((milestone) => (
                            <MilestoneColumn
                                key={milestone.token}
                                milestone={milestone}
                                canManage={canManage}
                                onAddTask={() =>
                                    openCreateTask(milestone.token)
                                }
                                onEditMilestone={() =>
                                    openEditMilestone(milestone)
                                }
                                onDeleteMilestone={() =>
                                    setMilestoneToDelete(milestone)
                                }
                                onEditTask={openEditTask}
                                onDeleteTask={setTaskToDelete}
                            />
                        ))}
                    </SortableContext>

                    <DragOverlay>
                        {activeTask ? (
                            <TaskCard
                                task={activeTask}
                                canManage={false}
                                onEdit={() => undefined}
                                onDelete={() => undefined}
                            />
                        ) : activeColumn ? (
                            <div className="w-full rounded-lg border bg-muted/60 p-2.5 font-semibold shadow">
                                {activeColumn.name}
                            </div>
                        ) : null}
                    </DragOverlay>
                </DndContext>

                <Can ability="milestones.create">
                    <button
                        type="button"
                        onClick={openCreateMilestone}
                        className="flex w-full items-center justify-center gap-1 rounded-lg border border-dashed p-3 text-sm text-muted-foreground hover:bg-muted/40"
                    >
                        <Plus className="h-4 w-4" /> Add milestone
                    </button>
                </Can>
            </div>

            {columns.length === 0 && !canManage && (
                <p className="text-sm text-muted-foreground">
                    No milestones yet.
                </p>
            )}

            <MilestoneFormSheet
                key={`milestone-${milestoneEditing?.token ?? 'new'}`}
                open={milestoneSheetOpen}
                onOpenChange={setMilestoneSheetOpen}
                projectToken={projectToken}
                assetToken={assetToken}
                milestone={milestoneEditing}
                onSuccess={() => setMilestoneSheetOpen(false)}
            />

            <TaskFormSheet
                key={`task-${taskEditing?.token ?? 'new'}-${taskDefaultMilestone ?? ''}`}
                open={taskSheetOpen}
                onOpenChange={setTaskSheetOpen}
                projectToken={projectToken}
                assetToken={assetToken}
                columns={columns}
                task={taskEditing}
                defaultMilestone={taskDefaultMilestone}
                userOptions={userOptions}
                referenceFileOptions={referenceFileOptions}
                dataTags={availableTags}
                onSuccess={() => setTaskSheetOpen(false)}
            />

            <ConfirmDialog
                open={milestoneToDelete !== null}
                onOpenChange={(o) => !o && setMilestoneToDelete(null)}
                title={`Delete ${milestoneToDelete?.name ?? 'milestone'}?`}
                description="This deletes the milestone and all of its tasks."
                confirmLabel="Delete"
                destructive
                onConfirm={deleteMilestone}
            />

            <ConfirmDialog
                open={taskToDelete !== null}
                onOpenChange={(o) => !o && setTaskToDelete(null)}
                title={`Delete ${taskToDelete?.name ?? 'task'}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={deleteTask}
            />
        </div>
    );
}
