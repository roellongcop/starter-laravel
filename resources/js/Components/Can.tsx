import { type ReactNode } from 'react';

import { usePermissions } from '@/lib/permissions';

interface CanProps {
    /** Single permission, e.g. "users.update". */
    ability?: string;
    /** Passes if the user has ANY of these permissions. */
    anyOf?: string[];
    /** Passes if the user has this role. */
    role?: string;
    children: ReactNode;
    fallback?: ReactNode;
}

/**
 * Conditionally render children based on the shared permissions/roles. UI gating
 * only — never the sole authorization mechanism.
 */
export default function Can({
    ability,
    anyOf,
    role,
    children,
    fallback = null,
}: CanProps) {
    const { can, canAny, hasRole } = usePermissions();

    const allowed =
        (ability ? can(ability) : false) ||
        (anyOf ? canAny(anyOf) : false) ||
        (role ? hasRole(role) : false);

    return <>{allowed ? children : fallback}</>;
}
