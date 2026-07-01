import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';
import { type PageProps } from '@/types';

export default function ForgotPassword({ status }: { status?: string }) {
    const { flash } = usePage<PageProps>().props;

    const { data, setData, post, processing, errors } = useForm({
        email: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('password.email'));
    };

    const showHint = () => {
        post(route('password.hint'), { preserveScroll: true });
    };

    return (
        <GuestLayout>
            <Head title="Forgot Password" />

            <h1 className="text-2xl font-bold tracking-tight">
                Forgot password
            </h1>
            <p className="mb-6 mt-1 text-sm text-muted-foreground">
                Enter your email and we'll send a reset link. You can also peek
                at your password hint as a reminder.
            </p>

            {status && (
                <div className="mb-4 rounded-md border bg-muted px-4 py-3 text-sm">
                    {status}
                </div>
            )}

            {flash?.hint && (
                <div className="mb-4 rounded-md border bg-muted px-4 py-3 text-sm">
                    <span className="font-medium">Password hint:</span>{' '}
                    {flash.hint}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="email" required>
                        Email
                    </Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1"
                        autoFocus
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1" />
                </div>

                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button
                        type="button"
                        variant="outline"
                        disabled={processing}
                        onClick={showHint}
                        className="sm:flex-1"
                    >
                        Show password hint
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing}
                        className="sm:flex-1"
                    >
                        Email reset link
                    </Button>
                </div>
            </form>
        </GuestLayout>
    );
}
