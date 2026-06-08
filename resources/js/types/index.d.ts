export interface User {
    token: string;
    name: string;
    email: string;
    email_verified_at?: string;
    avatar_url?: string | null;
    roles?: string[];
}

/** A user document as serialized by DocumentController/ProfileController. */
export interface AdminDocument {
    token: string;
    name: string;
    url: string;
    size: number;
    extension: string | null;
    created_at: string | null;
}

/**
 * Shape returned by the PHP `cursorResponse()` helper and consumed by
 * <CursorPager>. Keyset pagination: opaque cursors, Prev/Next only.
 */
export interface CursorResponse<T> {
    data: T[];
    next_cursor: string | null;
    prev_cursor: string | null;
    has_more: boolean;
    /** Exact result count — only present when the endpoint opts in. */
    total?: number;
}

export interface UserMetaRow {
    key: string;
    value: string | null;
}

/** A user as serialized by UserController (admin CRUD pages). */
export interface AdminUser {
    token: string;
    name: string;
    email: string;
    username: string | null;
    user_status: string;
    record_status: number;
    roles: string[];
    avatar_url?: string | null;
    created_at: string | null;
    // detailed-only
    password_hint?: string | null;
    avatar_file_token?: string | null;
    meta?: UserMetaRow[];
}

/** A role as serialized by RoleController (admin CRUD pages). */
export interface AdminRole {
    token: string;
    name: string;
    description: string | null;
    role_type: string | null;
    permissions_count: number;
    created_at: string | null;
    // detailed-only
    permissions?: string[];
    module_access?: Record<string, string[]>;
    main_navigation?: NavItem[] | null;
    priority?: number;
}

/** A module from the menu builder's palette (Navigation::catalog()). */
export interface MenuCatalogItem {
    key: string;
    label: string;
    icon?: string;
    href: string;
}

export interface SelectOption {
    value: string | number;
    label: string;
}

export interface DashboardMetric {
    label: string;
    icon: string;
    count: number;
    href: string;
}

export interface SearchHit {
    label: string;
    sublabel: string | null;
    href: string;
}

export interface SearchGroup {
    label: string;
    hits: SearchHit[];
}

/** A file as serialized by FileController. */
export interface AdminFile {
    token: string;
    original_name: string;
    extension: string | null;
    mime: string | null;
    size: number;
    tag: string | null;
    owner: string | null;
    created_at: string | null;
    // detailed-only
    disk?: string;
    path?: string | null;
    is_image?: boolean;
}

/** An IP entry as serialized by IpController. */
export interface AdminIp {
    token: string;
    ip_address: string;
    list_type: string;
    description: string | null;
    record_status: number;
    created_at: string | null;
}

/** A node in the sidebar tree shared as `navigation`. */
export interface NavItem {
    key?: string;
    label: string;
    icon?: string;
    href?: string;
    external?: boolean;
    children?: NavItem[];
}

export interface SharedAuth {
    /**
     * Typed non-null for ergonomics on authenticated pages (Breeze convention);
     * it is actually null at runtime for guests, but guest pages never read it.
     */
    user: User;
    /** Flat permission names, e.g. "users.update". */
    permissions: string[];
    /** module_access map: { resourceKey: [abilities] }. */
    modules: Record<string, string[]>;
}

export interface ThemeTokens {
    light: Record<string, string>;
    dark: Record<string, string>;
}

export interface BellNotification {
    id: string;
    message: string;
    link: string | null;
    read: boolean;
    created_at: string | null;
}

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    auth: SharedAuth;
    navigation: NavItem[];
    bell: {
        unread_count: number;
        recent: BellNotification[];
    };
    settings: {
        system: {
            app_name: string;
            timezone: string;
            default_theme: 'light' | 'dark' | 'system';
            auto_logout_seconds: number;
        };
    };
    brand: {
        favicon_url: string | null;
        square_logo_url: string | null;
        landscape_logo_url: string | null;
    };
    theme: ThemeTokens | null;
    flash: {
        success?: string | null;
        error?: string | null;
        hint?: string | null;
    };
};

export interface AdminNotification extends BellNotification {
    type: string;
}

export interface AdminSession {
    id: string;
    user: string | null;
    ip_address: string | null;
    browser: string;
    os: string;
    last_activity: string | null;
    is_current: boolean;
}

export interface AdminAudit {
    id: number;
    event: string;
    auditable_type: string;
    auditable_id: number | null;
    user: string;
    ip_address: string | null;
    browser: string;
    os: string;
    device: string;
    tags: string | null;
    created_at: string | null;
    // detailed-only
    old_values?: Record<string, unknown>;
    new_values?: Record<string, unknown>;
    url?: string | null;
    user_agent?: string | null;
}

export interface AdminLoginHistory {
    id: number;
    event: string;
    user: string;
    email: string | null;
    ip_address: string | null;
    browser: string;
    os: string;
    device: string;
    created_at: string | null;
}

export interface QueueStats {
    pending: number;
    failed: number;
}

export interface AdminBackup {
    token: string;
    filename: string | null;
    disk: string;
    size: number | null;
    status: string;
    error_message: string | null;
    created_at: string | null;
}

export interface AdminExport {
    token: string;
    format: string;
    resource: string;
    row_count: number | null;
    total_rows: number | null;
    processed_rows: number;
    status: string;
    error_message: string | null;
    created_at: string | null;
}

export interface AdminImport {
    token: string;
    resource: string;
    filename: string | null;
    total: number;
    success: number;
    failed: number;
    has_error_report: boolean;
    status: string;
    created_at: string | null;
}

/** A theme as serialized by ThemeController (admin CRUD pages). */
export interface AdminTheme {
    token: string;
    name: string;
    description: string | null;
    is_default: boolean;
    created_at: string | null;
    // detailed-only
    preview_image?: string | null;
    tokens?: ThemeTokens;
}
