import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Register() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        password_confirmation: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('register'), {
            onFinish: () => reset('password', 'password_confirmation'),
        });
    };

    return (
        <GuestLayout>
            <Head title="Register" />

            <h1 className="text-2xl font-bold tracking-tight">
                Create your account
            </h1>
            <p className="mb-6 mt-1 text-sm text-muted-foreground">
                Register to take a read-only tour of the backend.
            </p>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name" required>
                        Name
                    </Label>
                    <Input
                        id="name"
                        name="name"
                        value={data.name}
                        className="mt-1"
                        autoComplete="name"
                        autoFocus
                        required
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} className="mt-1" />
                </div>

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
                        autoComplete="username"
                        required
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1" />
                </div>

                <div>
                    <Label htmlFor="password" required>
                        Password
                    </Label>
                    <Input
                        id="password"
                        type="password"
                        name="password"
                        value={data.password}
                        className="mt-1"
                        autoComplete="new-password"
                        required
                        onChange={(e) => setData('password', e.target.value)}
                    />
                    <InputError message={errors.password} className="mt-1" />
                </div>

                <div>
                    <Label htmlFor="password_confirmation" required>
                        Confirm password
                    </Label>
                    <Input
                        id="password_confirmation"
                        type="password"
                        name="password_confirmation"
                        value={data.password_confirmation}
                        className="mt-1"
                        autoComplete="new-password"
                        required
                        onChange={(e) =>
                            setData('password_confirmation', e.target.value)
                        }
                    />
                    <InputError
                        message={errors.password_confirmation}
                        className="mt-1"
                    />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    Register
                </Button>

                <p className="text-center text-sm text-muted-foreground">
                    Already registered?{' '}
                    <Link
                        href={route('login')}
                        className="font-medium text-foreground hover:underline"
                    >
                        Log in
                    </Link>
                </p>
            </form>
        </GuestLayout>
    );
}
