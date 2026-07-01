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
import { ChevronsDownUp, ChevronsUpDown, Plus } from 'lucide-react';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import { Button } from '@/Components/ui/button';
import { toast } from '@/hooks/use-toast';
import {
    type AdminMilestone,
    type AdminTask,
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
    taskStatusOptions: SelectOption[];
}

const columnOf = (taskToken: string, cols: AdminMilestone[]): string | null =>
    cols.find((c) => c.tasks.some((t) => t.token === taskToken))?.token ?? null;

// Above this many total cards, columns start collapsed so a huge board (e.g. a
// month/day layout) doesn't mount every card up front — the user expands what they need.
const COLLAPSE_THRESHOLD = 60;

export default function MilestoneBoard({
    projectToken,
    assetToken,
    assetOrganization,
    milestones,
    canManage,
    taskStatusOptions,
}: Props) {
    const [columns, setColumns] = useState<AdminMilestone[]>(milestones);
    const [activeId, setActiveId] = useState<string | null>(null);
    // Tokens of collapsed milestones (view-only; not persisted server-side).
    // Large boards start fully collapsed so their cards aren't all mounted at once.
    const [collapsed, setCollapsed] = useState<Set<string>>(() =>
        milestones.reduce((sum, m) => sum + m.tasks.length, 0) >
        COLLAPSE_THRESHOLD
            ? new Set(milestones.map((m) => m.token))
            : new Set(),
    );
    // Client-side task filters (the board loads every task, so filtering is local).
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');

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
    // Bumped on every form open so the sheet's React key changes and the form
    // remounts — otherwise re-editing the same record reuses a stale useForm
    // instance seeded at first mount (it never re-reads the fresh props).
    const [milestoneFormNonce, setMilestoneFormNonce] = useState(0);
    const [taskFormNonce, setTaskFormNonce] = useState(0);
    const [milestoneToDelete, setMilestoneToDelete] =
        useState<AdminMilestone | null>(null);
    const [taskToDelete, setTaskToDelete] = useState<AdminTask | null>(null);

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
        setMilestoneFormNonce((n) => n + 1);
        setMilestoneSheetOpen(true);
    };
    const openEditMilestone = (m: AdminMilestone) => {
        setMilestoneEditing(m);
        setMilestoneFormNonce((n) => n + 1);
        setMilestoneSheetOpen(true);
    };
    const openCreateTask = (milestoneToken: string) => {
        setTaskEditing(null);
        setTaskDefaultMilestone(milestoneToken);
        setTaskFormNonce((n) => n + 1);
        setTaskSheetOpen(true);
    };
    // Stable reference so memoized TaskCards don't re-render when the board does.
    const openEditTask = useCallback((t: AdminTask) => {
        setTaskEditing(t);
        setTaskDefaultMilestone(undefined);
        setTaskFormNonce((n) => n + 1);
        setTaskSheetOpen(true);
    }, []);

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

    const allCollapsed =
        columns.length > 0 && columns.every((c) => collapsed.has(c.token));

    const toggleCollapse = (token: string) =>
        setCollapsed((prev) => {
            const next = new Set(prev);
            if (next.has(token)) {
                next.delete(token);
            } else {
                next.add(token);
            }
            return next;
        });

    const toggleAll = () =>
        setCollapsed(
            allCollapsed ? new Set() : new Set(columns.map((c) => c.token)),
        );

    // When a filter is active, drag/reorder is disabled (persistence must operate
    // on the full board, not a filtered subset) and empty columns are hidden.
    const isFiltering = search.trim() !== '' || statusFilter !== '';
    const displayColumns = useMemo(() => {
        if (!isFiltering) {
            return columns;
        }
        const q = search.trim().toLowerCase();

        return columns
            .map((column) => ({
                ...column,
                tasks: column.tasks.filter(
                    (task) =>
                        (q === '' ||
                            task.name.toLowerCase().includes(q) ||
                            (task.description ?? '')
                                .toLowerCase()
                                .includes(q)) &&
                        (statusFilter === '' || task.status === statusFilter),
                ),
            }))
            .filter((column) => column.tasks.length > 0);
    }, [columns, isFiltering, search, statusFilter]);

    return (
        <div>
            {columns.length > 0 && (
                <div className="mb-3 flex flex-wrap items-center justify-between gap-2">
                    <FilterBar onSubmit={() => undefined}>
                        <FilterBar.Search
                            value={search}
                            onChange={setSearch}
                            placeholder="Search tasks…"
                            withButton={false}
                            className="w-56"
                        />
                        <FilterBar.Select
                            value={statusFilter || undefined}
                            onChange={(v) => setStatusFilter(v ?? '')}
                            options={taskStatusOptions}
                            allLabel="All statuses"
                        />
                    </FilterBar>
                    <Button variant="outline" size="sm" onClick={toggleAll}>
                        {allCollapsed ? (
                            <ChevronsUpDown className="h-4 w-4" />
                        ) : (
                            <ChevronsDownUp className="h-4 w-4" />
                        )}
                        {allCollapsed ? 'Expand all' : 'Collapse all'}
                    </Button>
                </div>
            )}

            <div className="flex flex-col gap-4">
                <DndContext
                    sensors={sensors}
                    collisionDetection={closestCorners}
                    onDragStart={onDragStart}
                    onDragOver={onDragOver}
                    onDragEnd={onDragEnd}
                >
                    <SortableContext
                        items={displayColumns.map((c) => c.token)}
                        strategy={verticalListSortingStrategy}
                    >
                        {displayColumns.map((milestone) => (
                            <MilestoneColumn
                                key={milestone.token}
                                milestone={milestone}
                                canManage={canManage}
                                dragDisabled={isFiltering}
                                collapsed={collapsed.has(milestone.token)}
                                projectToken={projectToken}
                                assetToken={assetToken}
                                assetOrganization={assetOrganization}
                                taskStatusOptions={taskStatusOptions}
                                onToggleCollapse={() =>
                                    toggleCollapse(milestone.token)
                                }
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
                                projectToken={projectToken}
                                assetToken={assetToken}
                                assetOrganization={assetOrganization}
                                taskStatusOptions={taskStatusOptions}
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

                {!isFiltering && (
                    <Can ability="milestones.create">
                        <button
                            type="button"
                            onClick={openCreateMilestone}
                            className="flex w-full items-center justify-center gap-1 rounded-lg border border-dashed p-3 text-sm text-muted-foreground hover:bg-muted/40"
                        >
                            <Plus className="h-4 w-4" /> Add milestone
                        </button>
                    </Can>
                )}
            </div>

            {columns.length === 0 && !canManage && (
                <p className="text-sm text-muted-foreground">
                    No milestones yet.
                </p>
            )}

            {isFiltering && displayColumns.length === 0 && (
                <p className="mt-2 text-sm text-muted-foreground">
                    No tasks match your filters.
                </p>
            )}

            <MilestoneFormSheet
                key={`milestone-${milestoneEditing?.token ?? 'new'}-${milestoneFormNonce}`}
                open={milestoneSheetOpen}
                onOpenChange={setMilestoneSheetOpen}
                projectToken={projectToken}
                assetToken={assetToken}
                milestone={milestoneEditing}
                onSuccess={() => setMilestoneSheetOpen(false)}
            />

            <TaskFormSheet
                key={`task-${taskEditing?.token ?? 'new'}-${taskDefaultMilestone ?? ''}-${taskFormNonce}`}
                open={taskSheetOpen}
                onOpenChange={setTaskSheetOpen}
                projectToken={projectToken}
                assetToken={assetToken}
                columns={columns}
                task={taskEditing}
                defaultMilestone={taskDefaultMilestone}
                assetOrganization={assetOrganization}
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
