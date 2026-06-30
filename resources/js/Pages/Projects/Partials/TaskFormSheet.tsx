import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import AsyncMultiSelect from '@/Components/AsyncMultiSelect';
import AsyncSelect from '@/Components/AsyncSelect';
import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/Components/ui/sheet';
import { Switch } from '@/Components/ui/switch';
import { Textarea } from '@/Components/ui/textarea';
import { type AdminMilestone, type AdminTask } from '@/types';

type AssigneeField = 'assigned_to' | 'approver' | 'observer';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectToken: string;
    assetToken: string;
    columns: AdminMilestone[];
    task?: AdminTask | null;
    defaultMilestone?: string;
    /** The bound asset's organization token — scopes the reference-file + tag pickers. */
    assetOrganization: string | null;
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
    assetOrganization,
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

    // Assignee/approver/observer are a Team or Person inside the asset's org.
    const assigneeSelect = (field: AssigneeField, label: string) => (
        <div>
            <Label htmlFor={field}>{label}</Label>
            <AsyncSelect
                id={field}
                className="mt-1"
                value={data[field] || undefined}
                onChange={(v) => setData(field, v ?? '')}
                routeName="task-assignees.options"
                params={{ organization: assetOrganization || undefined }}
                disabled={!assetOrganization}
                disabledHint="No organization"
                allowClear
                allLabel="Unassigned"
                placeholder="Unassigned"
                dialogTitle="Select team or person"
                searchPlaceholder="Search teams & people…"
                emptyText="No teams or people in this organization."
            />
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
                        <Label htmlFor="task-name" required>
                            Name
                        </Label>
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
                        <Label htmlFor="task-milestone" required>
                            Milestone
                        </Label>
                        <AsyncSelect
                            id="task-milestone"
                            className="mt-1"
                            value={data.milestone || undefined}
                            onChange={(v) => setData('milestone', v ?? '')}
                            staticOptions={columns.map((column) => ({
                                value: column.token,
                                label: column.name,
                            }))}
                            placeholder="Select a milestone"
                            dialogTitle="Select milestone"
                            searchPlaceholder="Search milestones…"
                            emptyText="No milestones."
                            invalid={Boolean(errors.milestone)}
                        />
                        <InputError
                            message={errors.milestone}
                            className="mt-1"
                        />
                    </div>

                    {assigneeSelect('assigned_to', 'Assigned to')}
                    {assigneeSelect('approver', 'Approver')}
                    {assigneeSelect('observer', 'Observer')}

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
                        <AsyncSelect
                            id="task-reference"
                            className="mt-1"
                            value={data.reference_file || undefined}
                            onChange={(v) => setData('reference_file', v ?? '')}
                            routeName="reference-files.options"
                            params={{
                                organization: assetOrganization || undefined,
                            }}
                            allowClear
                            allLabel="None"
                            placeholder="None"
                            dialogTitle="Select reference file"
                            searchPlaceholder="Search reference files…"
                            emptyText="No reference files for this organization."
                        />
                        <InputError
                            message={errors.reference_file}
                            className="mt-1"
                        />
                    </div>

                    <div>
                        <Label htmlFor="task-tags">Tags</Label>
                        <AsyncMultiSelect
                            id="task-tags"
                            className="mt-1"
                            values={data.tags}
                            onChange={(values) => setData('tags', values)}
                            routeName="data-tags.options"
                            params={{
                                organization: assetOrganization || undefined,
                            }}
                            disabled={!assetOrganization}
                            disabledHint="No organization"
                            placeholder="Select tags"
                            title="Select tags"
                            emptyText="No tags for this organization."
                            searchPlaceholder="Search tags…"
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
