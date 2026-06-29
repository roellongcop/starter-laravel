import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import MultiSelect from '@/Components/MultiSelect';
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
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import {
    type AdminMilestone,
    type AdminTask,
    type DataTagOption,
    type SelectOption,
} from '@/types';

// Radix Select forbids an empty-string item value, so an unset user/reference
// uses this sentinel that maps back to '' (→ null on the server) on change.
const NONE = '__none';

type UserField = 'assigned_to' | 'approver' | 'observer';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectToken: string;
    assetToken: string;
    columns: AdminMilestone[];
    task?: AdminTask | null;
    defaultMilestone?: string;
    userOptions: SelectOption[];
    referenceFileOptions: SelectOption[];
    /** Already filtered to the asset's organization by the board. */
    dataTags: DataTagOption[];
    onSuccess: () => void;
}

export default function TaskFormSheet({
    open,
    onOpenChange,
    projectToken,
    assetToken,
    columns,
    task,
    defaultMilestone,
    userOptions,
    referenceFileOptions,
    dataTags,
    onSuccess,
}: Props) {
    const editing = Boolean(task);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: task?.name ?? '',
        description: task?.description ?? '',
        milestone:
            task?.milestone ?? defaultMilestone ?? columns[0]?.token ?? '',
        assigned_to: task?.assigned_to?.token ?? '',
        approver: task?.approver?.token ?? '',
        observer: task?.observer?.token ?? '',
        private: task?.private ?? false,
        due_date: task?.due_date ?? '',
        reference_file: task?.reference_file?.token ?? '',
        tags: task?.tags.map((tag) => tag.token) ?? [],
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSuccess();
            },
        };

        if (editing && task) {
            patch(
                route('projects.assets.tasks.update', [
                    projectToken,
                    assetToken,
                    task.token,
                ]),
                options,
            );
        } else {
            post(
                route('projects.assets.tasks.store', [
                    projectToken,
                    assetToken,
                ]),
                options,
            );
        }
    };

    const userSelect = (field: UserField, label: string) => (
        <div>
            <Label htmlFor={field}>{label}</Label>
            <Select
                value={data[field] || NONE}
                onValueChange={(v) => setData(field, v === NONE ? '' : v)}
            >
                <SelectTrigger id={field} className="mt-1">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value={NONE}>Unassigned</SelectItem>
                    {userOptions.map((u) => (
                        <SelectItem key={u.value} value={String(u.value)}>
                            {u.label}
                        </SelectItem>
                    ))}
                </SelectContent>
            </Select>
            <InputError message={errors[field]} className="mt-1" />
        </div>
    );

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full overflow-y-auto sm:max-w-md"
            >
                <SheetHeader>
                    <SheetTitle>
                        {editing ? `Edit ${task?.name}` : 'New task'}
                    </SheetTitle>
                    <SheetDescription>
                        A card belonging to a milestone.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <Label htmlFor="task-name">Name</Label>
                        <Input
                            id="task-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                            placeholder="Draft the proposal"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <Label htmlFor="task-description">Description</Label>
                        <Textarea
                            id="task-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1"
                            rows={3}
                        />
                        <InputError
                            message={errors.description}
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <Label htmlFor="task-milestone">Milestone</Label>
                        <Select
                            value={data.milestone}
                            onValueChange={(v) => setData('milestone', v)}
                        >
                            <SelectTrigger id="task-milestone" className="mt-1">
                                <SelectValue placeholder="Select a milestone" />
                            </SelectTrigger>
                            <SelectContent>
                                {columns.map((column) => (
                                    <SelectItem
                                        key={column.token}
                                        value={column.token}
                                    >
                                        {column.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            message={errors.milestone}
                            className="mt-1"
                        />
                    </div>

                    {userSelect('assigned_to', 'Assigned to')}
                    {userSelect('approver', 'Approver')}
                    {userSelect('observer', 'Observer')}

                    <div>
                        <Label htmlFor="task-due">Due date</Label>
                        <Input
                            id="task-due"
                            type="date"
                            value={data.due_date}
                            onChange={(e) =>
                                setData('due_date', e.target.value)
                            }
                            className="mt-1"
                        />
                        <InputError
                            message={errors.due_date}
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <Label htmlFor="task-reference">Reference file</Label>
                        <Select
                            value={data.reference_file || NONE}
                            onValueChange={(v) =>
                                setData('reference_file', v === NONE ? '' : v)
                            }
                        >
                            <SelectTrigger id="task-reference" className="mt-1">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={NONE}>None</SelectItem>
                                {referenceFileOptions.map((r) => (
                                    <SelectItem
                                        key={r.value}
                                        value={String(r.value)}
                                    >
                                        {r.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError
                            message={errors.reference_file}
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <Label htmlFor="task-tags">Tags</Label>
                        <MultiSelect
                            id="task-tags"
                            className="mt-1"
                            options={dataTags}
                            selected={data.tags}
                            onChange={(values) => setData('tags', values)}
                            placeholder="Select tags"
                            title="Select tags"
                            emptyText="No tags for this organization."
                        />
                        <InputError message={errors.tags} className="mt-1" />
                    </div>

                    <div className="flex items-center justify-between rounded-md border p-3">
                        <div>
                            <Label htmlFor="task-private">Private</Label>
                            <p className="text-xs text-muted-foreground">
                                Flag the task as private (shown with a lock).
                            </p>
                        </div>
                        <Switch
                            id="task-private"
                            checked={data.private}
                            onCheckedChange={(v) => setData('private', v)}
                        />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {editing ? 'Save changes' : 'Create task'}
                    </Button>
                </form>
            </SheetContent>
        </Sheet>
    );
}
