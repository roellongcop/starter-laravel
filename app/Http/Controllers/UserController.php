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
use Inertia\Inertia;
use Inertia\Response;

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
            ->with('roles:id,name')
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

            $user->syncRoles($request->array('roles'));
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

        $user->load('roles:id,name', 'meta');

        return Inertia::render('Users/Show', [
            'user' => $this->row($user, detailed: true),
            'documents' => $this->documentsFor($user),
        ]);
    }

    public function edit(User $user): Response
    {
        $this->authorize('update', $user);

        $user->load('roles:id,name', 'meta');

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
            'id' => $f->id,
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
            $user->syncRoles($request->array('roles'));
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

        return redirect()->route('users.index')
            ->with('success', 'User deleted.');
    }

    public function bulk(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'process' => ['required', 'in:active,in_active,delete'],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $this->authorize($validated['process'] === 'delete' ? 'delete' : 'update', User::class);

        $count = User::bulkAction($validated['process'], $validated['ids']);

        return back()->with('success', "{$count} user(s) updated.");
    }

    /**
     * Replace the user's meta rows with the submitted key/value set.
     *
     * @param  array<int, array{key?: string, value?: ?string}>  $meta
     */
    protected function syncMeta(User $user, array $meta): void
    {
        $user->meta()->delete();

        foreach ($meta as $row) {
            if (! empty($row['key'])) {
                $user->meta()->create(['key' => $row['key'], 'value' => $row['value'] ?? null]);
            }
        }
    }

    /**
     * Set the user's avatar to an existing file id (uploaded via /media by the
     * picker). Absent, the avatar is left unchanged.
     */
    protected function syncAvatar(User $user, Request $request): void
    {
        if ($request->filled('avatar_file_id')) {
            $user->update(['avatar_file_id' => $request->integer('avatar_file_id')]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(User $user, bool $detailed = false): array
    {
        $data = [
            'id' => $user->id,
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
            $data['avatar_file_id'] = $user->avatar_file_id;
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
