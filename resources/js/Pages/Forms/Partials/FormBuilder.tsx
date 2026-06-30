import {
    closestCenter,
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    PointerSensor,
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
import { useForm } from '@inertiajs/react';
import {
    ChevronDown,
    ChevronUp,
    Copy,
    GripVertical,
    Trash2,
} from 'lucide-react';
import { FormEventHandler, useState } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import InputError from '@/Components/InputError';
import OrganizationSelect from '@/Components/OrganizationSelect';
import { Badge } from '@/Components/ui/badge';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import {
    type AdminForm,
    type FieldType,
    type FormField,
    type FormFieldConfig,
    type SelectOption,
} from '@/types';
import FieldEditor from './FieldEditor';

interface Props {
    form?: AdminForm;
    fieldTypes: SelectOption[];
}

function defaultConfig(type: FieldType): FormFieldConfig {
    switch (type) {
        case 'date':
            return { include_time: false };
        case 'range':
            return { min: 0, max: 10, step: 1 };
        case 'list':
            return { multiple: false, items: ['Option 1'] };
        case 'duration':
            return {};
        default:
            return { placeholder: '' };
    }
}

export default function FormBuilder({ form, fieldTypes }: Props) {
    const editing = Boolean(form);
    const typeLabel = (type: FieldType) =>
        fieldTypes.find((t) => t.value === type)?.label ?? type;

    const { data, setData, post, patch, processing, errors } = useForm<{
        title: string;
        description: string;
        organization: string;
        form_fields: FormField[];
        tags: string[];
    }>({
        title: form?.title ?? '',
        description: form?.description ?? '',
        organization: form?.organization ?? '',
        form_fields: form?.form_fields ?? [],
        tags: form?.tags?.map((t) => t.token) ?? [],
    });

    const fieldErrors = errors as Record<string, string>;

    const changeOrganization = (value: string | undefined) => {
        // A tag belongs to exactly one org, so changing org invalidates the
        // current selection — reset tags whenever the organization changes.
        setData((current) => ({
            ...current,
            organization: value ?? '',
            tags: [],
        }));
    };

    const sensors = useSensors(
        useSensor(PointerSensor, { activationConstraint: { distance: 5 } }),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    const addField = (type: FieldType) =>
        setData('form_fields', [
            ...data.form_fields,
            {
                id: crypto.randomUUID(),
                type,
                label: '',
                description: '',
                required: false,
                config: defaultConfig(type),
            },
        ]);

    const updateField = (id: string, patch: Partial<FormField>) =>
        setData(
            'form_fields',
            data.form_fields.map((f) => (f.id === id ? { ...f, ...patch } : f)),
        );

    const removeField = (id: string) =>
        setData(
            'form_fields',
            data.form_fields.filter((f) => f.id !== id),
        );

    const duplicateField = (id: string) => {
        const idx = data.form_fields.findIndex((f) => f.id === id);
        if (idx < 0) return;
        const copy: FormField = {
            ...structuredClone(data.form_fields[idx]),
            id: crypto.randomUUID(),
        };
        const next = [...data.form_fields];
        next.splice(idx + 1, 0, copy);
        setData('form_fields', next);
    };

    const onDragEnd = (e: DragEndEvent) => {
        const { active, over } = e;
        if (!over || active.id === over.id) return;
        const oldIdx = data.form_fields.findIndex((f) => f.id === active.id);
        const newIdx = data.form_fields.findIndex((f) => f.id === over.id);
        if (oldIdx >= 0 && newIdx >= 0) {
            setData('form_fields', arrayMove(data.form_fields, oldIdx, newIdx));
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (editing && form) {
            patch(route('forms.update', form.token));
        } else {
            post(route('forms.store'));
        }
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <div className="grid max-w-2xl gap-4">
                <div>
                    <Label htmlFor="title" required>
                        Title
                    </Label>
                    <Input
                        id="title"
                        value={data.title}
                        onChange={(e) => setData('title', e.target.value)}
                        className="mt-1"
                        placeholder="Customer Feedback"
                    />
                    <InputError message={errors.title} className="mt-1" />
                </div>
                <div>
                    <Label htmlFor="description">Description</Label>
                    <Textarea
                        id="description"
                        value={data.description}
                        onChange={(e) => setData('description', e.target.value)}
                        className="mt-1"
                        rows={2}
                    />
                    <InputError message={errors.description} className="mt-1" />
                </div>
                <div>
                    <Label htmlFor="organization" required>
                        Organization
                    </Label>
                    <OrganizationSelect
                        id="organization"
                        className="mt-1"
                        value={data.organization || undefined}
                        onChange={changeOrganization}
                        invalid={Boolean(errors.organization)}
                    />
                    <InputError
                        message={errors.organization}
                        className="mt-1"
                    />
                </div>
                <div>
                    <Label htmlFor="tags">Tags</Label>
                    <AsyncMultiSelect
                        id="tags"
                        className="mt-1"
                        values={data.tags}
                        onChange={(values) => setData('tags', values)}
                        routeName="data-tags.options"
                        params={{
                            organization: data.organization || undefined,
                        }}
                        disabled={!data.organization}
                        disabledHint="Select an organization first"
                        placeholder="Select tags"
                        title="Select tags"
                        description="Only tags from the chosen organization are shown."
                        emptyText="No tags for this organization."
                        searchPlaceholder="Search tags…"
                    />
                    <InputError message={errors.tags} className="mt-1" />
                </div>
            </div>

            <div>
                <div className="mb-2 flex items-center justify-between">
                    <Label>Fields</Label>
                    <Select
                        onValueChange={(v) => addField(v as FieldType)}
                        value=""
                    >
                        <SelectTrigger className="h-9 w-44">
                            <SelectValue placeholder="Add field…" />
                        </SelectTrigger>
                        <SelectContent>
                            {fieldTypes.map((t) => (
                                <SelectItem
                                    key={t.value}
                                    value={String(t.value)}
                                >
                                    {t.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                {data.form_fields.length === 0 ? (
                    <div className="rounded-lg border border-dashed py-10 text-center text-sm text-muted-foreground">
                        No fields yet. Use “Add field…” to start building.
                    </div>
                ) : (
                    <DndContext
                        sensors={sensors}
                        collisionDetection={closestCenter}
                        onDragEnd={onDragEnd}
                    >
                        <SortableContext
                            items={data.form_fields.map((f) => f.id)}
                            strategy={verticalListSortingStrategy}
                        >
                            <div className="space-y-2">
                                {data.form_fields.map((field, i) => (
                                    <FieldRow
                                        key={field.id}
                                        field={field}
                                        typeLabel={typeLabel(field.type)}
                                        labelError={
                                            fieldErrors[
                                                `form_fields.${i}.label`
                                            ]
                                        }
                                        configError={
                                            fieldErrors[
                                                `form_fields.${i}.config.items`
                                            ] ??
                                            fieldErrors[
                                                `form_fields.${i}.config.max`
                                            ] ??
                                            fieldErrors[
                                                `form_fields.${i}.config.min`
                                            ]
                                        }
                                        onChange={(patch) =>
                                            updateField(field.id, patch)
                                        }
                                        onDuplicate={() =>
                                            duplicateField(field.id)
                                        }
                                        onRemove={() => removeField(field.id)}
                                    />
                                ))}
                            </div>
                        </SortableContext>
                    </DndContext>
                )}
                <InputError message={errors.form_fields} className="mt-2" />
            </div>

            <Button type="submit" disabled={processing}>
                {editing ? 'Save changes' : 'Create form'}
            </Button>
        </form>
    );
}

function FieldRow({
    field,
    typeLabel,
    labelError,
    configError,
    onChange,
    onDuplicate,
    onRemove,
}: {
    field: FormField;
    typeLabel: string;
    labelError?: string;
    configError?: string;
    onChange: (patch: Partial<FormField>) => void;
    onDuplicate: () => void;
    onRemove: () => void;
}) {
    const [open, setOpen] = useState(false);
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id: field.id });

    return (
        <div
            ref={setNodeRef}
            style={{ transform: CSS.Transform.toString(transform), transition }}
            className={`rounded-md border bg-background ${isDragging ? 'opacity-50' : ''}`}
        >
            <div className="flex items-center gap-2 p-2">
                <button
                    type="button"
                    className="cursor-grab text-muted-foreground"
                    aria-label="Drag to reorder"
                    {...attributes}
                    {...listeners}
                >
                    <GripVertical className="h-4 w-4" />
                </button>
                <Badge variant="secondary" className="shrink-0">
                    {typeLabel}
                </Badge>
                <Input
                    value={field.label}
                    onChange={(e) => onChange({ label: e.target.value })}
                    placeholder="Field label"
                    className="h-9"
                />
                <label className="flex shrink-0 items-center gap-2 text-xs text-muted-foreground">
                    <Switch
                        checked={field.required}
                        onCheckedChange={(v) => onChange({ required: v })}
                    />
                    Required
                </label>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    aria-label={open ? 'Collapse' : 'Expand'}
                    onClick={() => setOpen((o) => !o)}
                >
                    {open ? (
                        <ChevronUp className="h-4 w-4" />
                    ) : (
                        <ChevronDown className="h-4 w-4" />
                    )}
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    aria-label="Duplicate field"
                    onClick={onDuplicate}
                >
                    <Copy className="h-4 w-4" />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    aria-label="Remove field"
                    onClick={onRemove}
                >
                    <Trash2 className="h-4 w-4 text-destructive" />
                </Button>
            </div>

            {(labelError || configError) && (
                <div className="px-2 pb-2">
                    <InputError message={labelError ?? configError} />
                </div>
            )}

            {open && <FieldEditor field={field} onChange={onChange} />}
        </div>
    );
}
