import { useMemo } from 'react';
import { usePage } from '@inertiajs/react';

export function usePermissions() {
    const { auth } = usePage().props;

    const permissions = useMemo(
        () => new Set(auth.user?.permissions ?? []),
        [auth.user?.permissions],
    );

    const roles = useMemo(
        () => new Set(auth.user?.roles ?? []),
        [auth.user?.roles],
    );

    const can = (permission) => permissions.has(permission);

    const canAny = (permissionList) => permissionList.some((permission) => permissions.has(permission));

    const hasRole = (role) => roles.has(role);

    return {
        permissions,
        roles,
        can,
        canAny,
        hasRole,
    };
}
