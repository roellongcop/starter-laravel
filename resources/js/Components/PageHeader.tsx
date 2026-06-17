import { type ReactNode } from 'react';

import Breadcrumbs from '@/Components/Breadcrumbs';
import { type Crumb } from '@/types';

export default function PageHeader({
    title,
    description,
    breadcrumbs,
    actions,
}: {
    title: string;
    description?: string;
    breadcrumbs?: Crumb[];
    actions?: ReactNode;
}) {
    return (
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
            <div>
                {breadcrumbs && breadcrumbs.length > 0 && (
                    <Breadcrumbs trail={breadcrumbs} className="mb-2" />
                )}
                <h1 className="text-2xl font-semibold tracking-tight text-foreground">
                    {title}
                </h1>
                {description && (
                    <p className="mt-1 text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {actions && (
                <div className="flex items-center gap-2">{actions}</div>
            )}
        </div>
    );
}
