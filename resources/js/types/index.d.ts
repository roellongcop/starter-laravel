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

/** One segment of a PageHeader breadcrumb trail; the last crumb omits `href`. */
export interface Crumb {
    label: string;
    href?: string;
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

/** An organization as serialized by OrganizationController. */
export interface AdminOrganization {
    token: string;
    name: string;
    description: string | null;
    point_of_contact: string | null; // user token
    point_of_contact_name: string | null; // display name
    record_status: number;
    created_at: string | null;
}

/** A project as serialized by ProjectController. */
export interface AdminProject {
    token: string;
    name: string;
    description: string | null;
    private: boolean;
    status: string; // ProjectStatus value
    organization: string | null; // organization token
    organization_name: string | null; // display name
    tags: TagChip[];
    record_status: number;
    created_at: string | null;
}

/** An asset as serialized by AssetController. */
export interface AdminAsset {
    token: string;
    name: string;
    id_code: string;
    address: string;
    organization: string | null; // organization token
    organization_name: string | null; // display name
    tags: TagChip[];
    record_status: number;
    created_at: string | null;
}

/** An asset bound to a project — an AdminAsset plus its per-project pivot status. */
export interface ProjectAsset extends AdminAsset {
    status: string; // ProjectStatus value
}

/** A user reference on a board task (assignee / approver / observer). */
export interface BoardUser {
    token: string;
    name: string;
}

/** A task card as serialized by ProjectAssetBoardController. */
export interface AdminTask {
    token: string;
    name: string;
    description: string | null;
    milestone: string; // owning milestone token
    assigned_to: BoardUser | null;
    approver: BoardUser | null;
    observer: BoardUser | null;
    private: boolean;
    due_date: string | null; // YYYY-MM-DD
    reference_file: { token: string; name: string } | null;
    tags: TagChip[];
    position: number;
    record_status: number;
    created_at: string | null;
}

/** A milestone column with its ordered tasks, as serialized by ProjectAssetBoardController. */
export interface AdminMilestone {
    token: string;
    name: string;
    description: string | null;
    position: number;
    is_default: boolean; // the auto-seeded "Misc" column — not renamable/deletable
    record_status: number;
    created_at: string | null;
    tasks: AdminTask[];
}

/** A team category as serialized by TeamCategoryController. */
export interface AdminTeamCategory {
    token: string;
    name: string;
    description: string | null;
    organization: string | null; // organization token
    organization_name: string | null; // display name
    record_status: number;
    created_at: string | null;
}

/** An organization role as serialized by OrganizationRoleController. */
export interface AdminOrganizationRole {
    token: string;
    name: string;
    description: string | null;
    organization: string | null; // organization token
    organization_name: string | null; // display name
    record_status: number;
    created_at: string | null;
}

/** A team member roster row (a `people` row joined to its user + org-role). */
export interface TeamMember {
    token: string; // person token
    user: string; // user token
    name: string; // user's name
    role: string; // organization-role name
}

/** A team as serialized by TeamController. */
export interface AdminTeam {
    token: string;
    name: string;
    description: string | null;
    organization: string | null; // organization token
    organization_name: string | null; // display name
    team_category: string | null; // category token
    team_category_name: string | null;
    organization_role: string | null; // org-role token
    organization_role_name: string | null;
    members: string[]; // user tokens of current members (feeds the picker)
    members_count: number;
    record_status: number;
    created_at: string | null;
    // detailed-only (show page)
    roster?: TeamMember[];
}

/** A person (team member) as serialized by PersonController — read-only roster. */
export interface AdminPerson {
    token: string;
    name: string; // user's name
    team: string; // team name
    role: string; // organization-role name
    organization: string; // organization name
    created_at: string | null;
}

/** A reference (with an optional single file) as serialized by ReferenceFileController. */
export interface AdminReferenceFile {
    token: string;
    name: string;
    description: string | null;
    organization: string | null; // organization token
    organization_name: string | null; // display name
    file_token: string | null;
    file_name: string | null;
    file_url: string | null; // gated download url, when a file is attached
    tags: TagChip[];
    record_status: number;
    created_at: string | null;
}

/** A coloured tag as serialized by DataTagController. */
export interface AdminDataTag {
    token: string;
    name: string;
    description: string | null;
    color: string; // hex from the DataTag palette
    organization: string | null; // organization token
    organization_name: string | null; // display name
    record_status: number;
    created_at: string | null;
}

/** A SelectOption that also carries the org token it belongs to (cascading). */
export interface OrgScopedOption extends SelectOption {
    organization: string; // organization token
}

/** A tag attached to a taggable resource, as serialized for display chips. */
export interface TagChip {
    token: string;
    name: string;
    color: string; // hex from the DataTag palette
}

/** A DataTag picker option, org-scoped and carrying its swatch colour. */
export interface DataTagOption extends OrgScopedOption {
    color: string; // hex from the DataTag palette
}

/** The kind of a form field; mirrors App\Enums\FieldType. */
export type FieldType =
    | 'text'
    | 'paragraph'
    | 'date'
    | 'duration'
    | 'range'
    | 'list';

/** Per-type field settings (only the keys relevant to a field's type are set). */
export interface FormFieldConfig {
    placeholder?: string;
    include_time?: boolean;
    multiple?: boolean;
    items?: string[];
    min?: number;
    max?: number;
    step?: number;
    min_label?: string;
    max_label?: string;
}

/** One field definition in a Form's `form_fields` array. */
export interface FormField {
    id: string;
    type: FieldType;
    label: string;
    description?: string | null;
    required: boolean;
    config: FormFieldConfig;
}

/** A submitted answer value, keyed by field id in a response's `answers`. */
export type AnswerValue = string | number | string[] | null;

/** A form as serialized by FormController. */
export interface AdminForm {
    token: string;
    title: string;
    description: string | null;
    form_fields: FormField[];
    organization: string | null; // organization token
    organization_name: string | null; // display name
    responses_count: number | null;
    tags: TagChip[];
    record_status: number;
    created_at: string | null;
}

/** The lighter form payload the fill/response pages render against. */
export interface FormDefinition {
    token: string;
    title: string;
    description: string | null;
    form_fields: FormField[];
    organization_name: string | null;
}

/** A single submission, as serialized by FormResponseController. */
export interface AdminFormResponse {
    token: string;
    answers: Record<string, AnswerValue>;
    respondent: string | null;
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
    token: string;
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
    referrer?: string | null;
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
