<?php

namespace App\Http\Controllers;

use App\Enums\UserStatus;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Models\File;
use App\Models\Role;
use App\Models\User;
use App\Policies\BasePolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Inertia\Inertia;
use Inertia\Response;
use OwenIt\Auditing\Events\AuditCustom;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $search = trim((string) $request->string('search'));
        $dateFrom = trim((string) $request->string('date_from'));
        $dateTo = trim((string) $request->string('date_to'));
        $inactive = $request->boolean('inactive')
            && $request->user()->can(BasePolicy::VIEW_INACTIVE);

        $query = User::query()
            ->when($inactive, fn ($q) => $q->onlyInactive())
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")))
            ->when($dateFrom !== '', fn ($q) => $q->whereDate('users.created_at', '>=', $dateFrom))
            ->when($dateTo !== '', fn ($q) => $q->whereDate('users.created_at', '<=', $dateTo));

        $total = (clone $query)->count();

        $users = $query
            ->with(['roles:id,name', 'avatarFile'])
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('Users/Index', [
            'users' => cursorResponse($users, fn (User $u) => $this->row($u), $total),
            'filters' => ['search' => $search, 'inactive' => $inactive, 'date_from' => $dateFrom, 'date_to' => $dateTo],
            'can' => [
                'create' => $request->user()->can('users.create'),
                'update' => $request->user()->can('users.update'),
                'delete' => $request->user()->can('users.delete'),
                'viewInactive' => $request->user()->can(BasePolicy::VIEW_INACTIVE),
                'export' => $request->user()->can('exports.create'),
            ],
            'exportFormats' => ['csv', 'xls', 'xlsx', 'pdf'],
        ]);
    }

    public function create(): Response
    {
        $this->authorize('create', User::class);

        return Inertia::render('Users/Create', $this->formOptions());
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->safe()->only([
                'name', 'email', 'username', 'password', 'password_hint', 'user_status',
            ]));

            $this->syncRoles($user, $request->array('roles'));
            $this->syncMeta($user, $request->array('meta'));
            $this->syncAvatar($user, $request);

            return $user;
        });

        return redirect()->route('users.show', $user)
            ->with('success', 'User created.');
    }

    public function show(User $user): Response
    {
        $this->authorize('view', $user);

        $user->load('roles:id,name', 'meta', 'avatarFile');

        return Inertia::render('Users/Show', [
            'user' => $this->row($user, detailed: true),
            'documents' => $this->documentsFor($user),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles:id,name', 'meta', 'avatarFile');

        return Inertia::render('Users/Edit', [
            'user' => $this->row($user, detailed: true),
            'documents' => $this->documentsFor($user),
            ...$this->formOptions(),
        ]);
    }

    /**
     * The target user's documents, in the shape the document UI consumes.
     *
     * @return array<string, mixed>
     */
    protected function documentsFor(User $user): array
    {
        $documents = File::query()
            ->documents()
            ->ownedBy($user->id)
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'));

        return cursorResponse($documents, fn (File $f): array => [
            'token' => $f->token,
            'name' => $f->original_name,
            'url' => route('documents.download', $f),
            'size' => $f->size,
            'extension' => $f->extension,
            'created_at' => $f->created_at?->toIso8601String(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        DB::transaction(function () use ($request, $user) {
            $data = $request->safe()->only([
                'name', 'email', 'username', 'password_hint', 'user_status',
            ]);

            if ($request->filled('password')) {
                $data['password'] = $request->string('password')->toString();
            }

            $user->update($data);
            $this->syncRoles($user, $request->array('roles'));
            $this->syncMeta($user, $request->array('meta'));
            $this->syncAvatar($user, $request);
        });

        return redirect()->route('users.show', $user)
            ->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $user->delete();

        // If the delete came from the list, return there with its filters
        // (search, ?inactive=1) intact. From the (now-gone) show page, fall back
        // to the bare index so we don't redirect to a 404.
        $previous = url()->previous();
        $cameFromList = parse_url($previous, PHP_URL_PATH) === parse_url(route('users.index'), PHP_URL_PATH);

        return redirect()->to($cameFromList ? $previous : route('users.index'))
            ->with('success', 'User deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete'],
            'tokens' => ['required', 'array'],
            'tokens.*' => ['string'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', User::class);

        $count = User::bulkAction($validated['process'], $validated['tokens']);

        return back()->with('success', "{$count} user(s) updated.");
    }

    /**
     * Sync the user's roles, recording a custom audit entry when the set changes.
     *
     * spatie's syncRoles only touches the model_has_roles pivot — which owen-it does
     * not audit — so role changes would otherwise go untracked. We keep syncRoles for
     * its name resolution + permission-cache clearing and emit a manual audit on diff.
     *
     * @param  array<int, string>  $roles
     */
    protected function syncRoles(User $user, array $roles): void
    {
        $before = $user->roles()->orderBy('name')->pluck('name')->all();

        $user->syncRoles($roles);

        $after = $user->roles()->orderBy('name')->pluck('name')->all();

        if ($before === $after) {
            return;
        }

        $user->auditEvent = 'roles-synced';
        $user->isCustomEvent = true;
        $user->auditCustomOld = ['roles' => $before];
        $user->auditCustomNew = ['roles' => $after];

        Event::dispatch(new AuditCustom($user));
    }

    /**
     * Replace the user's meta rows with the submitted key/value set.
     *
     * @param  array<int, array{key?: string, value?: ?string}>  $meta
     */
    protected function syncMeta(User $user, array $meta): void
    {
        $keep = [];

        foreach ($meta as $row) {
            if (! empty($row['key'])) {
                // updateOrCreate only persists (and audits) when the row is new or its
                // value actually changed — Eloquent skips the UPDATE when nothing is
                // dirty — so an unchanged save no longer emits spurious meta audits.
                $user->meta()->updateOrCreate(
                    ['key' => $row['key']],
                    ['value' => $row['value'] ?? null],
                );
                $keep[] = $row['key'];
            }
        }

        // Remove only the keys the form no longer includes (audits a real deletion).
        $user->meta()->whereNotIn('key', $keep)->delete();
    }

    /**
     * Set the user's avatar to an existing file (uploaded via /media by the
     * picker), referenced by its public token. Absent, the avatar is unchanged.
     */
    protected function syncAvatar(User $user, Request $request): void
    {
        if ($request->filled('avatar_file_token')) {
            $fileId = File::where('token', $request->string('avatar_file_token'))->value('id');
            $user->update(['avatar_file_id' => $fileId]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(User $user, bool $detailed = false): array
    {
        $data = [
            'token' => $user->token,
            'name' => $user->name,
            'email' => $user->email,
            'username' => $user->username,
            'user_status' => $user->user_status->value,
            'record_status' => $user->record_status->value,
            'roles' => $user->roles->pluck('name'),
            'avatar_url' => $user->avatar_url,
            'created_at' => $user->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['password_hint'] = $user->password_hint;
            $data['avatar_file_token'] = $user->avatarFile?->token;
            $data['meta'] = $user->meta->map(fn ($m) => ['key' => $m->key, 'value' => $m->value])->values();
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formOptions(): array
    {
        return [
            'roleOptions' => Role::query()->orderBy('name')->pluck('name'),
            'statusOptions' => UserStatus::options(),
        ];
    }
}
