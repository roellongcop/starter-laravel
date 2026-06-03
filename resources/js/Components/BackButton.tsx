import { router } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';

import { Button } from '@/Components/ui/button';

interface Props {
    /** Where to go when there's no prior history (direct load / refresh). */
    fallback?: string;
    label?: string;
    className?: string;
}

/**
 * Returns to the previous page the user visited (Inertia history). On a direct
 * load with no in-app history it navigates to `fallback` so Back is never a
 * dead end. Reusable on any detail/edit page.
 */
export default function BackButton({
    fallback = '/',
    label = 'Back',
    className,
}: Props) {
    const goBack = () => {
        if (window.history.length > 1) {
            window.history.back();
        } else {
            router.visit(fallback);
        }
    };

    return (
        <Button variant="outline" onClick={goBack} className={className}>
            <ArrowLeft className="h-4 w-4" /> {label}
        </Button>
    );
}
