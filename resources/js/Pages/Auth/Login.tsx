import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Checkbox } from '@/Components/ui/checkbox';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Login({
    status,
    canResetPassword,
}: {
    status?: string;
    canResetPassword: boolean;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Log in" />

            <h1 className="text-2xl font-bold tracking-tight">Welcome back</h1>
            <p className="mb-6 mt-1 text-sm text-muted-foreground">
                Log in to continue to the dashboard.
            </p>

            {status && (
                <div className="mb-4 rounded-md border bg-muted px-4 py-3 text-sm">
                    {status}
                </div>
            )}

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        name="email"
                        value={data.email}
                        className="mt-1"
                        autoComplete="username"
                        autoFocus
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1" />
                </div>

                <div>
                    <Label htmlFor="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1"
                        autoComplete="current-password"
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-1" />
                </div>

                <div className="flex items-center gap-2">
                    <Checkbox
                        id="remember"
                        checked={data.remember}
                        onCheckedChange={(v) => setData('remember', v === true)}
                    />
                    <Label
                        htmlFor="remember"
                        className="text-sm font-normal text-muted-foreground"
                    >
                        Remember me
                    </Label>
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    Log in
                </Button>

                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    {canResetPassword ? (
                        <Link
                            href={route('password.request')}
                            className="transition-colors hover:text-foreground"
                        >
                            Forgot your password?
                        </Link>
                    ) : (
                        <span />
                    )}
                    <Link
                        href={route('contact')}
                        className="transition-colors hover:text-foreground"
                    >
                        Contact
                    </Link>
                </div>
            </form>
        </GuestLayout>
    );
}
