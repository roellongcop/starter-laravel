import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import Avatar from '@/Components/Avatar';
import ImagePicker, { type PickedImage } from '@/Components/ImagePicker';
import { Button } from '@/Components/ui/button';

export default function UpdateAvatarForm({
    className = '',
}: {
    className?: string;
}) {
    const user = usePage().props.auth.user;
    const [open, setOpen] = useState(false);
    const [saving, setSaving] = useState(false);

    const onPicked = (image: PickedImage) => {
        setSaving(true);
        router.post(
            route('profile.avatar.store'),
            { file_id: image.id },
            {
                preserveScroll: true,
                onFinish: () => setSaving(false),
            },
        );
    };

    const remove = () =>
        router.delete(route('profile.avatar.destroy'), {
            preserveScroll: true,
        });

    return (
        <section className={className}>
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    Profile Photo
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Upload a photo, reuse one you uploaded before, or take one
                    with your camera.
                </p>
            </header>

            <div className="mt-6 flex items-center gap-6">
                <Avatar name={user.name} src={user.avatar_url} size={80} />

                <div className="flex flex-wrap gap-3">
                    <Button onClick={() => setOpen(true)} disabled={saving}>
                        {saving ? 'Saving…' : 'Change photo'}
                    </Button>
                    {user.avatar_url && (
                        <Button variant="outline" onClick={remove}>
                            Remove
                        </Button>
                    )}
                </div>
            </div>

            <ImagePicker
                open={open}
                onOpenChange={setOpen}
                onPicked={onPicked}
                aspectRatio={1}
                title="Profile photo"
            />
        </section>
    );
}
