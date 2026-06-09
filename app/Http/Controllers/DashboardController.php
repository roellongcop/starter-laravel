<?php

namespace App\Http\Controllers;

use App\Filters\Primitives\SearchFilter;
use App\Models\Backup;
use App\Models\File;
use App\Models\Ip;
use App\Models\Role;
use App\Models\Theme;
use App\Models\User;
use App\Models\UserExport;
use App\Models\UserImport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('dashboard.index');

        $user = $request->user();

        return Inertia::render('Dashboard', [
            // Deferred: the tiles are ~12 COUNT queries (incl. jobs/notifications,
            // which grow), so the page shell + recent users paint immediately and
            // the metrics load in a follow-up request behind a skeleton.
            'metrics' => Inertia::defer(fn () => $this->metrics($user)),
            'recent' => [
                'users' => User::query()->latest()->take(5)->get(['token', 'name', 'email'])
                    ->map(fn ($u) => ['token' => $u->token, 'name' => $u->name, 'email' => $u->email]),
            ],
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $this->authorize('dashboard.search');

        $q = trim((string) $request->string('q'));
        $user = $request->user();
        $groups = [];

        if ($q !== '' && $user->can('users.index')) {
            $groups[] = [
                'label' => 'Users',
                'hits' => $this->searchGroup(User::query(), ['name', 'email'], $request)
                    ->take(5)->get()
                    ->map(fn ($u) => ['label' => $u->name, 'sublabel' => $u->email, 'href' => route('users.show', $u, false)])
                    ->all(),
            ];
        }

        if ($q !== '' && $user->can('roles.index')) {
            $groups[] = [
                'label' => 'Roles',
                'hits' => $this->searchGroup(Role::query(), ['name'], $request)->take(5)->get()
                    ->map(fn ($r) => ['label' => $r->name, 'sublabel' => $r->description, 'href' => route('roles.show', $r, false)])
                    ->all(),
            ];
        }

        if ($q !== '' && $user->can('files.index')) {
            $groups[] = [
                'label' => 'Files',
                'hits' => $this->searchGroup(File::query(), ['original_name', 'tag'], $request)
                    ->take(5)->get()
                    ->map(fn ($f) => ['label' => $f->original_name, 'sublabel' => $f->tag, 'href' => route('files.show', $f, false)])
                    ->all(),
            ];
        }

        if ($q !== '' && $user->can('ips.index')) {
            $groups[] = [
                'label' => 'IP Lists',
                'hits' => $this->searchGroup(Ip::query(), ['ip_address', 'description'], $request)
                    ->take(5)->get()
                    ->map(fn ($ip) => ['label' => $ip->ip_address, 'sublabel' => $ip->list_type->value, 'href' => route('ips.show', $ip, false)])
                    ->all(),
            ];
        }

        return response()->json([
            'groups' => array_values(array_filter($groups, fn ($g) => $g['hits'] !== [])),
        ]);
    }

    /**
     * Apply the shared multi-column SearchFilter (wildcards escaped,
     * cross-driver) to one federated search group. Reuses the list filters'
     * `q` param so the palette and the index lists escape input identically.
     *
     * @template TModel of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TModel>  $query
     * @param  array<int, string>  $columns
     * @return Builder<TModel>
     */
    protected function searchGroup(Builder $query, array $columns, Request $request): Builder
    {
        $filter = (new SearchFilter(columns: $columns, key: 'q'))->forRequest($request);

        // handle() expects Builder<Model>; Builder's template is invariant so
        // Builder<TModel> trips a static check, but it returns the same builder.
        /** @var Builder<TModel> $filtered */
        $filtered = $filter->handle($query, fn (Builder $next): Builder => $next); // @phpstan-ignore argument.type

        return $filtered;
    }

    /**
     * Permission-gated metric tiles.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function metrics(User $user): array
    {
        $tiles = [];

        $add = function (string $ability, string $label, string $icon, int $count, string $href) use ($user, &$tiles): void {
            if ($user->can($ability)) {
                $tiles[] = compact('label', 'icon', 'count', 'href');
            }
        };

        $add('users.index', 'Users', 'Users', User::count(), route('users.index', absolute: false));
        $add('roles.index', 'Roles', 'KeyRound', Role::count(), route('roles.index', absolute: false));
        $add('files.index', 'Files', 'Files', File::count(), route('files.index', absolute: false));
        $add('ips.index', 'IP Lists', 'Network', Ip::count(), route('ips.index', absolute: false));
        $add('themes.index', 'Themes', 'Palette', Theme::count(), route('themes.index', absolute: false));
        $add('backups.index', 'Backups', 'Archive', Backup::count(), route('backups.index', absolute: false));
        $add('exports.index', 'My Exports', 'Download', UserExport::where('user_id', $user->id)->count(), route('exports.index', absolute: false));
        $add('imports.index', 'My Imports', 'Upload', UserImport::where('user_id', $user->id)->count(), route('imports.index', absolute: false));
        $add('queue.index', 'Queued Jobs', 'ListChecks', DB::table('jobs')->count(), route('queue.index', absolute: false));

        // Unread notifications is always relevant to the current user.
        $tiles[] = [
            'label' => 'Unread Alerts',
            'icon' => 'Bell',
            'count' => $user->unreadNotifications()->count(),
            'href' => route('notifications.index', absolute: false),
        ];

        return $tiles;
    }
}
