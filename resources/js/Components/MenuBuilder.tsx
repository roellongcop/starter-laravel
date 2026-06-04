import {
    closestCorners,
    DndContext,
    type DragEndEvent,
    type DragOverEvent,
    DragOverlay,
    type DragStartEvent,
    KeyboardSensor,
    PointerSensor,
    useDroppable,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { ChevronDown, ChevronUp, GripVertical, Plus, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { iconNames, NavIcon } from '@/lib/navIcons';
import { type MenuCatalogItem, type NavItem } from '@/types';

const TOP = '__top';

interface Leaf {
    id: string;
    kind: 'module' | 'link';
    label: string;
    icon?: string;
    key?: string;
    href?: string;
    external?: boolean;
}

interface Group {
    id: string;
    label: string;
    icon?: string;
}

interface BuilderState {
    groups: Group[];
    items: Record<string, Leaf[]>; // keyed by TOP and each group id
}

let counter = 0;
const uid = (p: string) => `${p}-${Date.now().toString(36)}-${counter++}`;

/** NavItem[] (stored shape) → builder state. */
function parse(value: NavItem[]): BuilderState {
    const groups: Group[] = [];
    const items: Record<string, Leaf[]> = { [TOP]: [] };

    const toLeaf = (n: NavItem): Leaf => ({
        id: uid('leaf'),
        kind: n.key ? 'module' : 'link',
        label: n.label,
        icon: n.icon,
        key: n.key,
        href: n.href,
        external: n.external,
    });

    for (const node of value) {
        if (node.children) {
            const g: Group = {
                id: uid('grp'),
                label: node.label,
                icon: node.icon,
            };
            groups.push(g);
            items[g.id] = node.children.map(toLeaf);
        } else {
            items[TOP].push(toLeaf(node));
        }
    }
    return { groups, items };
}

/** Builder state → NavItem[] (stored shape). */
function serialize(state: BuilderState): NavItem[] {
    const leafToNav = (l: Leaf): NavItem =>
        l.kind === 'module'
            ? { key: l.key, label: l.label, icon: l.icon, href: l.href }
            : { label: l.label, icon: l.icon, href: l.href, external: true };

    return [
        ...state.items[TOP].map(leafToNav),
        ...state.groups.map((g) => ({
            label: g.label,
            icon: g.icon,
            children: (state.items[g.id] ?? []).map(leafToNav),
        })),
    ];
}

export default function MenuBuilder({
    value,
    onChange,
    catalog,
    accessibleKeys,
}: {
    value: NavItem[];
    onChange: (next: NavItem[]) => void;
    catalog: MenuCatalogItem[];
    accessibleKeys: string[];
}) {
    const [state, setStateRaw] = useState<BuilderState>(() => parse(value));
    const [activeId, setActiveId] = useState<string | null>(null);

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    // Commit state + emit the serialized tree to the parent form.
    const setState = (next: BuilderState) => {
        setStateRaw(next);
        onChange(serialize(next));
    };

    const containerOf = (id: string, s: BuilderState): string | null => {
        if (id === TOP || s.items[id]) return id; // dropped on a container itself
        for (const c of Object.keys(s.items)) {
            if (s.items[c].some((l) => l.id === id)) return c;
        }
        return null;
    };

    const allLeaves = () => Object.values(state.items).flat();
    const usedKeys = new Set(
        allLeaves()
            .map((l) => l.key)
            .filter(Boolean),
    );
    const addableModules = catalog.filter(
        (c) => accessibleKeys.includes(c.key) && !usedKeys.has(c.key),
    );

    const onDragStart = (e: DragStartEvent) => setActiveId(String(e.active.id));

    const onDragOver = (e: DragOverEvent) => {
        const { active, over } = e;
        if (!over) return;
        const from = containerOf(String(active.id), state);
        const to = containerOf(String(over.id), state);
        if (!from || !to || from === to) return;

        // Move the dragged leaf into the target container (end, or before over item).
        const next = structuredClone(state);
        const moving = next.items[from].find((l) => l.id === active.id);
        if (!moving) return;
        next.items[from] = next.items[from].filter((l) => l.id !== active.id);
        const overIdx = next.items[to].findIndex((l) => l.id === over.id);
        next.items[to].splice(
            overIdx >= 0 ? overIdx : next.items[to].length,
            0,
            moving,
        );
        setStateRaw(next); // mid-drag: don't emit yet
    };

    const onDragEnd = (e: DragEndEvent) => {
        setActiveId(null);
        const { active, over } = e;
        if (!over) {
            onChange(serialize(state));
            return;
        }
        const container = containerOf(String(over.id), state);
        const from = containerOf(String(active.id), state);
        if (container && from === container && active.id !== over.id) {
            const list = state.items[container];
            const oldIdx = list.findIndex((l) => l.id === active.id);
            const newIdx = list.findIndex((l) => l.id === over.id);
            if (oldIdx >= 0 && newIdx >= 0) {
                setState({
                    ...state,
                    items: {
                        ...state.items,
                        [container]: arrayMove(list, oldIdx, newIdx),
                    },
                });
                return;
            }
        }
        onChange(serialize(state)); // commit cross-container move from onDragOver
    };

    // --- mutations -------------------------------------------------------
    const addGroup = () => {
        // Seed the new group's items list so containerOf() recognizes it as a
        // drop target (an empty group with no items entry rejects drops).
        const id = uid('grp');
        setState({
            ...state,
            groups: [
                ...state.groups,
                { id, label: 'New group', icon: 'FolderOpen' },
            ],
            items: { ...state.items, [id]: [] },
        });
    };

    const addModule = (key: string) => {
        const mod = catalog.find((c) => c.key === key);
        if (!mod) return;
        const leaf: Leaf = {
            id: uid('leaf'),
            kind: 'module',
            label: mod.label,
            icon: mod.icon,
            key: mod.key,
            href: mod.href,
        };
        setState({
            ...state,
            items: { ...state.items, [TOP]: [...state.items[TOP], leaf] },
        });
    };

    const addLink = () => {
        const leaf: Leaf = {
            id: uid('leaf'),
            kind: 'link',
            label: 'New link',
            icon: 'Link',
            href: 'https://',
        };
        setState({
            ...state,
            items: { ...state.items, [TOP]: [...state.items[TOP], leaf] },
        });
    };

    const updateLeaf = (container: string, id: string, patch: Partial<Leaf>) =>
        setState({
            ...state,
            items: {
                ...state.items,
                [container]: state.items[container].map((l) =>
                    l.id === id ? { ...l, ...patch } : l,
                ),
            },
        });

    const removeLeaf = (container: string, id: string) =>
        setState({
            ...state,
            items: {
                ...state.items,
                [container]: state.items[container].filter((l) => l.id !== id),
            },
        });

    const updateGroup = (id: string, patch: Partial<Group>) =>
        setState({
            ...state,
            groups: state.groups.map((g) =>
                g.id === id ? { ...g, ...patch } : g,
            ),
        });

    const removeGroup = (id: string) => {
        // Don't lose its items — move them back to the top level.
        const orphans = state.items[id] ?? [];
        const items: Record<string, Leaf[]> = {
            ...state.items,
            [TOP]: [...state.items[TOP], ...orphans],
        };
        delete items[id];
        setState({
            ...state,
            groups: state.groups.filter((g) => g.id !== id),
            items,
        });
    };

    const moveGroup = (id: string, dir: -1 | 1) => {
        const idx = state.groups.findIndex((g) => g.id === id);
        const next = idx + dir;
        if (next < 0 || next >= state.groups.length) return;
        setState({ ...state, groups: arrayMove(state.groups, idx, next) });
    };

    const activeLeaf = activeId
        ? allLeaves().find((l) => l.id === activeId)
        : null;

    return (
        <div className="space-y-4">
            <div className="flex flex-wrap items-center gap-2">
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addGroup}
                >
                    <Plus className="h-4 w-4" /> Group
                </Button>
                <Select onValueChange={addModule} value="">
                    <SelectTrigger className="h-9 w-44">
                        <SelectValue placeholder="Add module…" />
                    </SelectTrigger>
                    <SelectContent>
                        {addableModules.length === 0 ? (
                            <SelectItem value="__none" disabled>
                                No modules available
                            </SelectItem>
                        ) : (
                            addableModules.map((m) => (
                                <SelectItem key={m.key} value={m.key}>
                                    {m.label}
                                </SelectItem>
                            ))
                        )}
                    </SelectContent>
                </Select>
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addLink}
                >
                    <Plus className="h-4 w-4" /> Link
                </Button>
                <p className="text-xs text-muted-foreground">
                    Drag items to reorder or move them between groups.
                </p>
            </div>

            <DndContext
                sensors={sensors}
                collisionDetection={closestCorners}
                onDragStart={onDragStart}
                onDragOver={onDragOver}
                onDragEnd={onDragEnd}
            >
                <Container
                    id={TOP}
                    title="Top level"
                    leaves={state.items[TOP]}
                    onUpdate={updateLeaf}
                    onRemove={removeLeaf}
                />

                {state.groups.map((g, i) => (
                    <div key={g.id} className="rounded-md border">
                        <div className="flex items-center gap-2 border-b bg-muted/40 p-2">
                            <Select
                                value={g.icon ?? ''}
                                onValueChange={(v) =>
                                    updateGroup(g.id, { icon: v })
                                }
                            >
                                <SelectTrigger className="h-8 w-12 px-2">
                                    <NavIcon
                                        name={g.icon}
                                        className="h-4 w-4"
                                    />
                                </SelectTrigger>
                                <SelectContent>
                                    {iconNames.map((n) => (
                                        <SelectItem key={n} value={n}>
                                            <span className="flex items-center gap-2">
                                                <NavIcon
                                                    name={n}
                                                    className="h-4 w-4"
                                                />
                                                {n}
                                            </span>
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <Input
                                value={g.label}
                                onChange={(e) =>
                                    updateGroup(g.id, { label: e.target.value })
                                }
                                className="h-8 font-medium"
                            />
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                title="Move up"
                                aria-label="Move up"
                                disabled={i === 0}
                                onClick={() => moveGroup(g.id, -1)}
                            >
                                <ChevronUp className="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                title="Move down"
                                aria-label="Move down"
                                disabled={i === state.groups.length - 1}
                                onClick={() => moveGroup(g.id, 1)}
                            >
                                <ChevronDown className="h-4 w-4" />
                            </Button>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                title="Remove group"
                                aria-label="Remove group"
                                onClick={() => removeGroup(g.id)}
                            >
                                <X className="h-4 w-4 text-destructive" />
                            </Button>
                        </div>
                        <Container
                            id={g.id}
                            leaves={state.items[g.id] ?? []}
                            onUpdate={updateLeaf}
                            onRemove={removeLeaf}
                        />
                    </div>
                ))}

                <DragOverlay>
                    {activeLeaf ? (
                        <div className="flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm shadow">
                            <NavIcon
                                name={activeLeaf.icon}
                                className="h-4 w-4"
                            />
                            {activeLeaf.label}
                        </div>
                    ) : null}
                </DragOverlay>
            </DndContext>
        </div>
    );
}

function Container({
    id,
    title,
    leaves,
    onUpdate,
    onRemove,
}: {
    id: string;
    title?: string;
    leaves: Leaf[];
    onUpdate: (container: string, id: string, patch: Partial<Leaf>) => void;
    onRemove: (container: string, id: string) => void;
}) {
    const { setNodeRef } = useDroppable({ id });

    return (
        <div ref={setNodeRef} className="p-2">
            {title && (
                <p className="mb-1 px-1 text-xs font-semibold uppercase tracking-wide text-muted-foreground/70">
                    {title}
                </p>
            )}
            <SortableContext
                items={leaves.map((l) => l.id)}
                strategy={verticalListSortingStrategy}
            >
                <div className="space-y-1">
                    {leaves.length === 0 && (
                        <p className="rounded border border-dashed px-3 py-2 text-xs text-muted-foreground">
                            Drop items here
                        </p>
                    )}
                    {leaves.map((leaf) => (
                        <LeafRow
                            key={leaf.id}
                            leaf={leaf}
                            container={id}
                            onUpdate={onUpdate}
                            onRemove={onRemove}
                        />
                    ))}
                </div>
            </SortableContext>
        </div>
    );
}

function LeafRow({
    leaf,
    container,
    onUpdate,
    onRemove,
}: {
    leaf: Leaf;
    container: string;
    onUpdate: (container: string, id: string, patch: Partial<Leaf>) => void;
    onRemove: (container: string, id: string) => void;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: leaf.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`flex items-center gap-2 rounded-md border bg-background p-2 ${
                isDragging ? 'opacity-50' : ''
            }`}
        >
            <button
                type="button"
                className="cursor-grab text-muted-foreground"
                {...attributes}
                {...listeners}
            >
                <GripVertical className="h-4 w-4" />
            </button>

            <Select
                value={leaf.icon ?? ''}
                onValueChange={(v) => onUpdate(container, leaf.id, { icon: v })}
            >
                <SelectTrigger className="h-8 w-12 px-2">
                    <NavIcon name={leaf.icon} className="h-4 w-4" />
                </SelectTrigger>
                <SelectContent>
                    {iconNames.map((n) => (
                        <SelectItem key={n} value={n}>
                            <span className="flex items-center gap-2">
                                <NavIcon name={n} className="h-4 w-4" />
                                {n}
                            </span>
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>

            <Input
                value={leaf.label}
                onChange={(e) =>
                    onUpdate(container, leaf.id, { label: e.target.value })
                }
                className="h-8"
            />

            {leaf.kind === 'link' && (
                <Input
                    value={leaf.href ?? ''}
                    onChange={(e) =>
                        onUpdate(container, leaf.id, { href: e.target.value })
                    }
                    placeholder="https://…"
                    className="h-8 w-48"
                />
            )}

            <Button
                type="button"
                size="icon"
                variant="ghost"
                title="Remove item"
                aria-label="Remove item"
                onClick={() => onRemove(container, leaf.id)}
            >
                <X className="h-4 w-4 text-destructive" />
            </Button>
        </div>
    );
}
