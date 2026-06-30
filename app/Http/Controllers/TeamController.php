<?php

namespace App\Http\Controllers;

use App\Filters\TeamFilters;
use App\Http\Requests\StoreTeamRequest;
use App\Http\Requests\UpdateTeamRequest;
use App\Models\Organization;
use App\Models\OrganizationRole;
use App\Models\Person;
use App\Models\Team;
use App\Models\TeamCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class TeamController extends Controller
{
    public function index(Request $request, TeamFilters $filters): Response
    {
        $this->authorize('viewAny', Team::class);

        $teams = $filters->apply(Team::query()->with(['organization', 'category', 'role', 'people.user']))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Team $team) => $this->row($team));

        return Inertia::render('Teams/Index', [
            'teams' => Inertia::scroll($teams),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function store(StoreTeamRequest $request): RedirectResponse
    {
        $this->authorize('create', Team::class);

        DB::transaction(function () use ($request): void {
            $team = Team::create($this->resolveRelations($request->validated()));
            $this->syncMembers($team, $request->array('members'));
        });

        return redirect()->route('teams.index')->with('success', 'Team created.');
    }

    public function show(Team $team): Response
    {
        $this->authorize('view', $team);

        $team->load(['organization', 'category', 'role', 'people.user', 'people.role']);

        return Inertia::render('Teams/Show', [
            'team' => $this->row($team, detailed: true),
        ]);
    }

    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        $this->authorize('update', $team);

        DB::transaction(function () use ($request, $team): void {
            $team->update($this->resolveRelations($request->validated()));
            $this->syncMembers($team, $request->array('members'));
        });

        return back()->with('success', 'Team updated.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        $this->authorize('delete', $team);

        // People rows cascade with the team at the database level.
        $team->delete();

        return redirect()->route('teams.index')->with('success', 'Team deleted.');
    }

    /**
     * Translate the org/category/role tokens into ids (only tokens cross the
     * wire) and drop the members array, which is handled by syncMembers().
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveRelations(array $data): array
    {
        $data['organization_id'] = Organization::where('token', $data['organization'])->value('id');
        $data['team_category_id'] = TeamCategory::where('token', $data['team_category'])->value('id');
        $data['organization_role_id'] = OrganizationRole::where('token', $data['organization_role'])->value('id');

        unset($data['organization'], $data['team_category'], $data['organization_role'], $data['members']);

        return $data;
    }

    /**
     * Reconcile the team's member roster (the `people` rows) with the submitted
     * set of user tokens. Each member inherits the team's org-role + organization;
     * deselected members are removed. Membership is keyed on (team, user) only, so
     * a user may belong to teams in many organizations with different roles.
     *
     * @param  array<int, string>  $userTokens
     */
    protected function syncMembers(Team $team, array $userTokens): void
    {
        $userIds = User::whereIn('token', $userTokens)->pluck('id')->all();

        // Drop members no longer selected (0 guards against an empty whereNotIn).
        $team->people()->whereNotIn('user_id', $userIds ?: [0])->delete();

        $existing = $team->people()->pluck('user_id')->all();

        foreach (array_diff($userIds, $existing) as $userId) {
            $team->people()->create([
                'user_id' => $userId,
                'organization_role_id' => $team->organization_role_id,
                'organization_id' => $team->organization_id,
            ]);
        }

        // Keep the whole roster aligned with the team's current role/org (handles
        // a team role/org change without re-adding members). Scoped to this team.
        $team->people()->update([
            'organization_role_id' => $team->organization_role_id,
            'organization_id' => $team->organization_id,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Team $team, bool $detailed = false): array
    {
        $data = [
            'token' => $team->token,
            'name' => $team->name,
            'description' => $team->description,
            'organization' => $team->organization->token,
            'organization_name' => $team->organization->name,
            'team_category' => $team->category->token,
            'team_category_name' => $team->category->name,
            'organization_role' => $team->role->token,
            'organization_role_name' => $team->role->name,
            // User tokens of the current members — feeds the form's members picker.
            'members' => $team->people->map(fn (Person $person) => $person->user->token)->values()->all(),
            'members_count' => $team->people->count(),
            'record_status' => $team->record_status->value,
            'created_at' => $team->created_at?->toIso8601String(),
        ];

        if ($detailed) {
            $data['roster'] = $team->people->map(fn (Person $person) => [
                'token' => $person->token,
                'user' => $person->user->token,
                'name' => $person->user->name,
                'role' => $person->role->name,
            ])->values()->all();
        }

        return $data;
    }
}
