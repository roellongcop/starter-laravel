import { usePage } from '@inertiajs/react';

import { type PageProps } from '@/types';

/**
 * Access the shared auth/permission props and convenience checks. Mirrors the
 * server: `can()` tests the flat permission list, `hasModule()` the
 * module_access map, `hasRole()` the role names. These gate UI only — the server
 * still authorizes every request.
 */
export function usePermissions() {
    const { auth } = usePage<PageProps>().props;
    const permissions = auth.permissions ?? [];
    const modules = auth.modules ?? {};
    const roles = auth.user?.roles ?? [];

    const can = (ability: string) => permissions.includes(ability);
    const canAny = (abilities: string[]) => abilities.some(can);
    const hasModule = (key: string) => (modules[key]?.length ?? 0) > 0;
    const hasRole = (role: string) => roles.includes(role);

    return { permissions, modules, roles, can, canAny, hasModule, hasRole };
}
