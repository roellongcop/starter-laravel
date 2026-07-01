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
import { Textarea } from '@/Components/ui/textarea';
import { type AdminRequirement } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectToken: string;
    assetToken: string;
    taskToken: string;
    requirement?: AdminRequirement | null;
    /** The owning task's organization token — scopes the reference-file / form / tag pickers. */
    assetOrganization: string | null;
    onSuccess: () => void;
}

export default function RequirementFormSheet({
    open,
    onOpenChange,
    projectToken,
    assetToken,
    taskToken,
    requirement,
    assetOrganization,
    onSuccess,
}: Props) {
    const editing = Boolean(requirement);

    const { data, setData, post, patch, transform, processing, errors, reset } =
        useForm({
            name: requirement?.name ?? '',
            description: requirement?.description ?? '',
            minimum_files: requirement?.minimum_files?.toString() ?? '',
            maximum_files: requirement?.maximum_files?.toString() ?? '',
            reference_file: requirement?.reference_file?.token ?? '',
            form: requirement?.form?.token ?? '',
            tags: requirement?.tags.map((tag) => tag.token) ?? [],
        });

    // Numbers cross the wire as numbers (or null when blank) — the string inputs
    // keep the field controlled without coercing an empty box to 0.
    transform((d) => ({
        ...d,
        minimum_files: d.minimum_files === '' ? null : Number(d.minimum_files),
        maximum_files: d.maximum_files === '' ? null : Number(d.maximum_files),
    }));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        const options = {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onSuccess();
            },
        };

        if (editing && requirement) {
            patch(
                route('projects.assets.tasks.requirements.update', [
                    projectToken,
                    assetToken,
                    taskToken,
                    requirement.token,
                ]),
                options,
            );
        } else {
            post(
                route('projects.assets.tasks.requirements.store', [
                    projectToken,
                    assetToken,
                    taskToken,
                ]),
                options,
            );
        }
    };

    return (
        <Sheet open={open} onOpenChange={onOpenChange}>
            <SheetContent
                side="right"
                className="w-full overflow-y-auto sm:max-w-md"
            >
                <SheetHeader>
                    <SheetTitle>
                        {editing
                            ? `Edit ${requirement?.name}`
                            : 'New requirement'}
                    </SheetTitle>
                    <SheetDescription>
                        A deliverable attached to this task.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <Label htmlFor="requirement-name" required>
                            Name
                        </Label>
                        <Input
                            id="requirement-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                            placeholder="Signed contract"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <Label htmlFor="requirement-description">
                            Description
                        </Label>
                        <Textarea
                            id="requirement-description"
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

                    <div className="grid grid-cols-2 gap-3">
                        <div>
                            <Label htmlFor="requirement-min">
                                Minimum files
                            </Label>
                            <Input
                                id="requirement-min"
                                type="number"
                                min={0}
                                value={data.minimum_files}
                                onChange={(e) =>
                                    setData('minimum_files', e.target.value)
                                }
                                className="mt-1"
                                placeholder="0"
                            />
                            <InputError
                                message={errors.minimum_files}
                                className="mt-1"
                            />
                        </div>
                        <div>
                            <Label htmlFor="requirement-max">
                                Maximum files
                            </Label>
                            <Input
                                id="requirement-max"
                                type="number"
                                min={0}
                                value={data.maximum_files}
                                onChange={(e) =>
                                    setData('maximum_files', e.target.value)
                                }
                                className="mt-1"
                                placeholder="No limit"
                            />
                            <InputError
                                message={errors.maximum_files}
                                className="mt-1"
                            />
                        </div>
                    </div>

                    <div>
                        <Label htmlFor="requirement-reference">
                            Reference file
                        </Label>
                        <AsyncSelect
                            id="requirement-reference"
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
                        <Label htmlFor="requirement-form">Form</Label>
                        <AsyncSelect
                            id="requirement-form"
                            className="mt-1"
                            value={data.form || undefined}
                            onChange={(v) => setData('form', v ?? '')}
                            routeName="forms.options"
                            params={{
                                organization: assetOrganization || undefined,
                            }}
                            allowClear
                            allLabel="None"
                            placeholder="None"
                            dialogTitle="Select form"
                            searchPlaceholder="Search forms…"
                            emptyText="No forms for this organization."
                        />
                        <InputError message={errors.form} className="mt-1" />
                    </div>

                    <div>
                        <Label htmlFor="requirement-tags">Tags</Label>
                        <AsyncMultiSelect
                            id="requirement-tags"
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

                    <Button type="submit" disabled={processing}>
                        {editing ? 'Save changes' : 'Create requirement'}
                    </Button>
                </form>
            </SheetContent>
        </Sheet>
    );
}
