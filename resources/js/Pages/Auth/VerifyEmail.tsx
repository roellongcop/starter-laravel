import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import { Button } from '@/Components/ui/button';
import GuestLayout from '@/Layouts/GuestLayout';

export default function VerifyEmail({ status }: { status?: string }) {
    const { post, processing } = useForm({});

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('verification.send'));
    };

    return (
        <GuestLayout>
            <Head title="Email Verification" />

            <h1 className="text-2xl font-bold tracking-tight">
                Verify your email
            </h1>
            <p className="mb-4 mt-1 text-sm text-muted-foreground">
                Thanks for signing up! Please verify your email by clicking the
                link we just sent you. Didn't get it? We'll gladly send another.
            </p>

            {status === 'verification-link-sent' && (
                <div className="mb-4 rounded-md border bg-muted px-4 py-3 text-sm">
                    A new verification link has been sent to your email address.
                </div>
            )}

            <form
                onSubmit={submit}
                className="flex items-center justify-between"
            >
                <Button type="submit" disabled={processing}>
                    Resend verification email
                </Button>

                <Button asChild variant="ghost" size="sm">
                    <Link href={route('profile.edit')}>View Profile</Link>
                </Button>
            </form>
        </GuestLayout>
    );
}
