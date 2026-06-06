import { Head, useForm } from '@inertiajs/react';
import { Send } from 'lucide-react';
import { FormEventHandler } from 'react';

import InputError from '@/Components/InputError';
import { Button } from '@/Components/ui/button';
import { Input } from '@/Components/ui/input';
import { Label } from '@/Components/ui/label';
import { Textarea } from '@/Components/ui/textarea';
import GuestLayout from '@/Layouts/GuestLayout';

export default function Contact() {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        message: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('contact.store'), {
            preserveScroll: true,
            onSuccess: () => reset(),
        });
    };

    return (
        <GuestLayout>
            <Head title="Contact" />

            <h1 className="text-2xl font-bold tracking-tight">Get in touch</h1>
            <p className="mb-6 mt-1 text-sm text-muted-foreground">
                Have a project in mind or a question? Send a message and I'll
                get back to you.
            </p>

            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">Name</Label>
                    <Input
                        id="name"
                        className="mt-1"
                        value={data.name}
                        autoFocus
                        onChange={(e) => setData('name', e.target.value)}
                    />
                    <InputError message={errors.name} className="mt-1" />
                </div>

                <div>
                    <Label htmlFor="email">Email</Label>
                    <Input
                        id="email"
                        type="email"
                        className="mt-1"
                        value={data.email}
                        onChange={(e) => setData('email', e.target.value)}
                    />
                    <InputError message={errors.email} className="mt-1" />
                </div>

                <div>
                    <Label htmlFor="message">Message</Label>
                    <Textarea
                        id="message"
                        rows={5}
                        className="mt-1"
                        value={data.message}
                        onChange={(e) => setData('message', e.target.value)}
                    />
                    <InputError message={errors.message} className="mt-1" />
                </div>

                <Button type="submit" className="w-full" disabled={processing}>
                    <Send className="h-4 w-4" /> Send message
                </Button>
            </form>
        </GuestLayout>
    );
}
