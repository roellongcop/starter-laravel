import { useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

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
import { type AdminMilestone } from '@/types';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    projectToken: string;
    assetToken: string;
    milestone?: AdminMilestone | null;
    onSuccess: () => void;
}

export default function MilestoneFormSheet({
    open,
    onOpenChange,
    projectToken,
    assetToken,
    milestone,
    onSuccess,
}: Props) {
    const editing = Boolean(milestone);

    const { data, setData, post, patch, processing, errors, reset } = useForm({
        name: milestone?.name ?? '',
        description: milestone?.description ?? '',
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

        if (editing && milestone) {
            patch(
                route('projects.assets.milestones.update', [
                    projectToken,
                    assetToken,
                    milestone.token,
                ]),
                options,
            );
        } else {
            post(
                route('projects.assets.milestones.store', [
                    projectToken,
                    assetToken,
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
                        {editing ? `Edit ${milestone?.name}` : 'New milestone'}
                    </SheetTitle>
                    <SheetDescription>
                        A column on the board, holding tasks.
                    </SheetDescription>
                </SheetHeader>

                <form onSubmit={submit} className="mt-6 space-y-4">
                    <div>
                        <Label htmlFor="milestone-name">Name</Label>
                        <Input
                            id="milestone-name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                            placeholder="Design"
                        />
                        <InputError message={errors.name} className="mt-1" />
                    </div>

                    <div>
                        <Label htmlFor="milestone-description">
                            Description
                        </Label>
                        <Textarea
                            id="milestone-description"
                            value={data.description}
                            onChange={(e) =>
                                setData('description', e.target.value)
                            }
                            className="mt-1"
                            rows={3}
                            placeholder="What this stage covers (optional)"
                        />
                        <InputError
                            message={errors.description}
                            className="mt-1"
                        />
                    </div>

                    <Button type="submit" disabled={processing}>
                        {editing ? 'Save changes' : 'Create milestone'}
                    </Button>
                </form>
            </SheetContent>
        </Sheet>
    );
}
