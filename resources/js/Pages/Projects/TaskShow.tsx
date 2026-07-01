import { Head, router } from '@inertiajs/react';
import axios from 'axios';
import {
    Clock,
    FileText,
    Files,
    Lock,
    MoreHorizontal,
    Paperclip,
    Pencil,
    Plus,
    Trash2,
    User as UserIcon,
    Users as UsersIcon,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import Can from '@/Components/Can';
import ConfirmDialog from '@/Components/ConfirmDialog';
import FilterBar from '@/Components/FilterBar';
import PageHeader from '@/Components/PageHeader';
import StatusBadge from '@/Components/StatusBadge';
import StatusDropdown from '@/Components/StatusDropdown';
import TagEditor from '@/Components/TagEditor';
import { Button } from '@/Components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/Components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { usePermissions } from '@/lib/permissions';
import {
    type AdminRequirement,
    type BoardAssignee,
    type Crumb,
    type SelectOption,
    type TaskDetail,
} from '@/types';
import RequirementFormSheet from './Partials/RequirementFormSheet';

interface Props {
    project: { token: string; name: string };
    asset: { token: string; name: string; organization: string | null };
    milestone: { token: string; name: string };
    task: TaskDetail;
    taskStatusOptions: SelectOption[];
}

/** A task-details row (label + team/person value, — when unset) for the detail dl. */
function Assignee({
    label,
    value,
}: {
    label: string;
    value: BoardAssignee | null;
}) {
    return (
        <>
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="flex min-w-0 items-center gap-1.5">
                {value ? (
                    <>
                        {value.type === 'team' ? (
                            <UsersIcon className="h-3.5 w-3.5 shrink-0" />
                        ) : (
                            <UserIcon className="h-3.5 w-3.5 shrink-0" />
                        )}
                        <span className="truncate">{value.name}</span>
                    </>
                ) : (
                    <span className="text-muted-foreground">—</span>
                )}
            </dd>
        </>
    );
}

/** File-count bounds for the requirement meta row (null when unbounded). */
function filesLabel(requirement: AdminRequirement): string | null {
    const { minimum_files: min, maximum_files: max } = requirement;
    if (min == null && max == null) {
        return null;
    }
    if (min != null && max != null) {
        return `${min}–${max} files`;
    }
    if (min != null) {
        return `≥ ${min} files`;
    }
    return `≤ ${max} files`;
}

export default function TaskShow({
    project,
    asset,
    milestone,
    task,
    taskStatusOptions,
}: Props) {
    const { can } = usePermissions();
    const [formOpen, setFormOpen] = useState(false);
    const [editing, setEditing] = useState<AdminRequirement | null>(null);
    const [formNonce, setFormNonce] = useState(0);
    const [toDelete, setToDelete] = useState<AdminRequirement | null>(null);
    // Client-side requirement filters (the task loads all of its requirements).
    const [reqSearch, setReqSearch] = useState('');
    const [reqStatus, setReqStatus] = useState('');

    const filteredRequirements = useMemo(() => {
        const q = reqSearch.trim().toLowerCase();

        return task.requirements.filter(
            (requirement) =>
                (q === '' ||
                    requirement.name.toLowerCase().includes(q) ||
                    (requirement.description ?? '')
                        .toLowerCase()
                        .includes(q)) &&
                (reqStatus === '' || requirement.status === reqStatus),
        );
    }, [task.requirements, reqSearch, reqStatus]);

    const breadcrumbs: Crumb[] = [
        { label: 'Projects', href: route('projects.index') },
        { label: project.name, href: route('projects.show', project.token) },
        {
            label: asset.name,
            href: route('projects.assets.show', [project.token, asset.token]),
        },
        { label: task.name },
    ];

    const openCreate = () => {
        setEditing(null);
        setFormNonce((n) => n + 1);
        setFormOpen(true);
    };
    const openEdit = (requirement: AdminRequirement) => {
        setEditing(requirement);
        setFormNonce((n) => n + 1);
        setFormOpen(true);
    };

    const deleteRequirement = () => {
        if (!toDelete) {
            return;
        }
        router.delete(
            route('projects.assets.tasks.requirements.destroy', [
                project.token,
                asset.token,
                task.token,
                toDelete.token,
            ]),
            {
                preserveScroll: true,
                onFinish: () => setToDelete(null),
            },
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title={`${task.name} · ${asset.name}`} />

            <PageHeader
                title={task.name}
                description={`Task in ${milestone.name}`}
                breadcrumbs={breadcrumbs}
            />

            <div className="space-y-6">
                {/* Task details — label/value dl, matching the project & asset detail cards. */}
                <Card>
                    <CardContent className="p-4">
                        <dl className="grid grid-cols-[7rem_1fr] items-center gap-x-4 gap-y-2.5 text-sm">
                            <dt className="text-muted-foreground">Status</dt>
                            <dd>
                                <Can
                                    ability="tasks.update"
                                    fallback={
                                        <StatusBadge status={task.status} />
                                    }
                                >
                                    <StatusDropdown
                                        value={task.status}
                                        options={taskStatusOptions}
                                        onSelect={(status) =>
                                            axios.patch(
                                                route(
                                                    'projects.assets.tasks.status',
                                                    [
                                                        project.token,
                                                        asset.token,
                                                        task.token,
                                                    ],
                                                ),
                                                { status },
                                            )
                                        }
                                    />
                                </Can>
                            </dd>

                            <Assignee
                                label="Assigned to"
                                value={task.assigned_to}
                            />
                            <Assignee label="Approver" value={task.approver} />
                            <Assignee label="Observer" value={task.observer} />

                            <dt className="text-muted-foreground">Due date</dt>
                            <dd className="flex items-center gap-1.5">
                                {task.due_date ? (
                                    <>
                                        <Clock className="h-3.5 w-3.5" />
                                        {task.due_date}
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </dd>

                            <dt className="text-muted-foreground">Private</dt>
                            <dd className="flex items-center gap-1.5">
                                {task.private ? (
                                    <>
                                        <Lock className="h-3.5 w-3.5" />
                                        Yes
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        No
                                    </span>
                                )}
                            </dd>

                            <dt className="text-muted-foreground">Reference</dt>
                            <dd className="flex min-w-0 items-center gap-1.5">
                                {task.reference_file ? (
                                    <>
                                        <Paperclip className="h-3.5 w-3.5 shrink-0" />
                                        <span className="truncate">
                                            {task.reference_file.name}
                                        </span>
                                    </>
                                ) : (
                                    <span className="text-muted-foreground">
                                        —
                                    </span>
                                )}
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Description
                            </dt>
                            <dd className="whitespace-pre-line">
                                {task.description || '—'}
                            </dd>

                            <dt className="self-start text-muted-foreground">
                                Tags
                            </dt>
                            <dd>
                                <TagEditor
                                    tags={task.tags}
                                    organization={asset.organization}
                                    type="tasks"
                                    token={task.token}
                                    canEdit={can('tasks.update')}
                                />
                            </dd>
                        </dl>
                    </CardContent>
                </Card>

                {/* Requirements */}
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between space-y-0 p-4 pb-3">
                        <CardTitle className="text-base">
                            Requirements ({task.requirements.length})
                        </CardTitle>
                        <Can ability="requirements.create">
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={openCreate}
                            >
                                <Plus className="mr-1 h-4 w-4" /> Add
                                requirement
                            </Button>
                        </Can>
                    </CardHeader>
                    <CardContent className="space-y-3 p-4 pt-0">
                        {task.requirements.length === 0 ? (
                            <p className="rounded border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                                No requirements yet.
                            </p>
                        ) : (
                            <>
                                <FilterBar onSubmit={() => undefined}>
                                    <FilterBar.Search
                                        value={reqSearch}
                                        onChange={setReqSearch}
                                        placeholder="Search requirements…"
                                        withButton={false}
                                        className="w-56"
                                    />
                                    <FilterBar.Select
                                        value={reqStatus || undefined}
                                        onChange={(v) => setReqStatus(v ?? '')}
                                        options={taskStatusOptions}
                                        allLabel="All statuses"
                                    />
                                </FilterBar>

                                {filteredRequirements.length === 0 ? (
                                    <p className="rounded border border-dashed px-3 py-6 text-center text-sm text-muted-foreground">
                                        No requirements match your filters.
                                    </p>
                                ) : (
                                    <ul className="space-y-2">
                                        {filteredRequirements.map(
                                            (requirement) => (
                                                <li
                                                    key={requirement.token}
                                                    className="relative flex min-h-[6.5rem] items-stretch overflow-hidden rounded-md border bg-background text-sm shadow-sm"
                                                >
                                                    {/* Status as a leading addon cell (matches project/asset/task cards). */}
                                                    <div className="flex items-stretch border-r bg-muted/30">
                                                        <Can
                                                            ability="requirements.update"
                                                            fallback={
                                                                <span className="flex items-center px-3">
                                                                    <StatusBadge
                                                                        status={
                                                                            requirement.status
                                                                        }
                                                                    />
                                                                </span>
                                                            }
                                                        >
                                                            <StatusDropdown
                                                                iconOnly
                                                                variant="ghost"
                                                                className="h-full w-auto rounded-none px-3"
                                                                value={
                                                                    requirement.status
                                                                }
                                                                options={
                                                                    taskStatusOptions
                                                                }
                                                                onSelect={(
                                                                    status,
                                                                ) =>
                                                                    axios.patch(
                                                                        route(
                                                                            'projects.assets.tasks.requirements.status',
                                                                            [
                                                                                project.token,
                                                                                asset.token,
                                                                                task.token,
                                                                                requirement.token,
                                                                            ],
                                                                        ),
                                                                        {
                                                                            status,
                                                                        },
                                                                    )
                                                                }
                                                            />
                                                        </Can>
                                                    </div>

                                                    <div className="flex min-w-0 flex-1 flex-col p-2.5">
                                                        <div className="flex items-start gap-1.5">
                                                            <p className="line-clamp-1 min-w-0 flex-1 font-medium leading-snug">
                                                                {
                                                                    requirement.name
                                                                }
                                                            </p>
                                                            <Can ability="requirements.update">
                                                                <DropdownMenu>
                                                                    <DropdownMenuTrigger
                                                                        asChild
                                                                    >
                                                                        <Button
                                                                            size="icon"
                                                                            variant="ghost"
                                                                            className="h-6 w-6 shrink-0"
                                                                            aria-label="Requirement actions"
                                                                        >
                                                                            <MoreHorizontal className="h-4 w-4" />
                                                                        </Button>
                                                                    </DropdownMenuTrigger>
                                                                    <DropdownMenuContent align="end">
                                                                        <DropdownMenuItem
                                                                            onClick={() =>
                                                                                openEdit(
                                                                                    requirement,
                                                                                )
                                                                            }
                                                                        >
                                                                            <Pencil className="mr-2 h-4 w-4" />{' '}
                                                                            Edit
                                                                        </DropdownMenuItem>
                                                                        <Can ability="requirements.delete">
                                                                            <DropdownMenuItem
                                                                                onClick={() =>
                                                                                    setToDelete(
                                                                                        requirement,
                                                                                    )
                                                                                }
                                                                                className="text-destructive focus:text-destructive"
                                                                            >
                                                                                <Trash2 className="mr-2 h-4 w-4" />{' '}
                                                                                Delete
                                                                            </DropdownMenuItem>
                                                                        </Can>
                                                                    </DropdownMenuContent>
                                                                </DropdownMenu>
                                                            </Can>
                                                        </div>

                                                        {requirement.description ? (
                                                            <p className="mt-1 line-clamp-1 text-xs text-muted-foreground">
                                                                {
                                                                    requirement.description
                                                                }
                                                            </p>
                                                        ) : (
                                                            <p className="mt-1 text-xs italic text-muted-foreground/70">
                                                                No description
                                                            </p>
                                                        )}

                                                        <div className="mt-auto flex flex-col gap-1.5 pt-2">
                                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-muted-foreground">
                                                                <span className="flex items-center gap-1">
                                                                    <Files className="h-3.5 w-3.5" />
                                                                    {filesLabel(
                                                                        requirement,
                                                                    ) ??
                                                                        'Any files'}
                                                                </span>
                                                                {requirement.reference_file && (
                                                                    <span className="flex min-w-0 items-center gap-1">
                                                                        <Paperclip className="h-3.5 w-3.5 shrink-0" />
                                                                        <span className="truncate">
                                                                            {
                                                                                requirement
                                                                                    .reference_file
                                                                                    .name
                                                                            }
                                                                        </span>
                                                                    </span>
                                                                )}
                                                                {requirement.form && (
                                                                    <span className="flex min-w-0 items-center gap-1">
                                                                        <FileText className="h-3.5 w-3.5 shrink-0" />
                                                                        <span className="truncate">
                                                                            {
                                                                                requirement
                                                                                    .form
                                                                                    .title
                                                                            }
                                                                        </span>
                                                                    </span>
                                                                )}
                                                            </div>

                                                            <TagEditor
                                                                tags={
                                                                    requirement.tags
                                                                }
                                                                organization={
                                                                    asset.organization
                                                                }
                                                                type="requirements"
                                                                token={
                                                                    requirement.token
                                                                }
                                                                canEdit={can(
                                                                    'requirements.update',
                                                                )}
                                                                singleRow
                                                            />
                                                        </div>
                                                    </div>
                                                </li>
                                            ),
                                        )}
                                    </ul>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>

            <RequirementFormSheet
                key={`requirement-${editing?.token ?? 'new'}-${formNonce}`}
                open={formOpen}
                onOpenChange={setFormOpen}
                projectToken={project.token}
                assetToken={asset.token}
                taskToken={task.token}
                requirement={editing}
                assetOrganization={asset.organization}
                onSuccess={() => setFormOpen(false)}
            />

            <ConfirmDialog
                open={toDelete !== null}
                onOpenChange={(o) => !o && setToDelete(null)}
                title={`Delete ${toDelete?.name ?? 'requirement'}?`}
                confirmLabel="Delete"
                destructive
                onConfirm={deleteRequirement}
            />
        </AuthenticatedLayout>
    );
}
