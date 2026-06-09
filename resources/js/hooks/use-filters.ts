import { router } from '@inertiajs/react';
import { useCallback, useState } from 'react';

/**
 * Flat filter values as the backend echoes them — the page's `filters` prop.
 * Strings/booleans/numbers only, mirroring the controller's echoBack() output.
 */
export type FilterValues = Record<
    string,
    string | number | boolean | undefined
>;

/** A flat query-param object: empty/falsy values are omitted (key absent). */
export type FilterParams = Record<string, string | number | undefined>;

interface UseFiltersOptions<T extends FilterValues> {
    /** Route name, e.g. 'users.index'. */
    route: string;
    /** The `filters` prop echoed by the controller — seeds initial state. */
    initial: T;
    /**
     * Per-key serialize override. Return `undefined` to omit the key from the
     * URL. Defaults reproduce the legacy convention: boolean -> 1 | undefined,
     * '' -> undefined, else the value.
     */
    serialize?: Partial<{
        [K in keyof T]: (value: T[K]) => string | number | undefined;
    }>;
}

const defaultSerialize = (value: unknown): string | number | undefined => {
    if (
        value === undefined ||
        value === null ||
        value === '' ||
        value === false
    ) {
        return undefined;
    }
    if (value === true) {
        return 1;
    }
    return value as string | number;
};

/**
 * Standardizes list-page search/filter state + URL sync. Text inputs are
 * "pending" (set, then submit); toggles/selects apply immediately. Re-filtering
 * drops the cursor param so the keyset list returns to the first page, while
 * <CursorPager> re-reads these same flat params from the URL when paginating.
 */
export function useFilters<T extends FilterValues>({
    route: routeName,
    initial,
    serialize,
}: UseFiltersOptions<T>) {
    // Seed from the FULL filters prop so echoed-but-unedited keys (e.g. an
    // `event` a page doesn't expose) survive a search submit untouched.
    const [values, setValues] = useState<T>(initial);

    const toParams = useCallback(
        (vals: T): FilterParams => {
            const out: FilterParams = {};
            (Object.keys(vals) as (keyof T)[]).forEach((key) => {
                const fn = serialize?.[key] ?? defaultSerialize;
                const param = fn(vals[key]);
                if (param !== undefined) {
                    out[key as string] = param;
                }
            });
            return out;
        },
        [serialize],
    );

    const visit = useCallback(
        (vals: T) =>
            router.get(route(routeName), toParams(vals), {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }),
        [routeName, toParams],
    );

    /** Update one pending value locally WITHOUT navigating (text inputs). */
    const set = useCallback(<K extends keyof T>(key: K, value: T[K]) => {
        setValues((prev) => ({ ...prev, [key]: value }));
    }, []);

    /** Submit the current pending state (search form onSubmit / Enter). */
    const submit = useCallback(() => visit(values), [visit, values]);

    /** Merge a partial change and navigate immediately (toggles/selects). */
    const apply = useCallback(
        (partial: Partial<T>) => {
            setValues((prev) => {
                const next = { ...prev, ...partial };
                visit(next);
                return next;
            });
        },
        [visit],
    );

    /** Clear every key (booleans -> false, strings -> '') and reload. */
    const reset = useCallback(() => {
        const cleared = Object.fromEntries(
            Object.keys(values).map((key) => [
                key,
                typeof values[key] === 'boolean' ? false : '',
            ]),
        ) as T;
        setValues(cleared);
        visit(cleared);
    }, [values, visit]);

    return { values, set, apply, submit, reset };
}
