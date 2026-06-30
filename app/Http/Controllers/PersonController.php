<?php

namespace App\Http\Controllers;

use App\Filters\PersonFilters;
use App\Models\Person;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only roster across all teams: each `people` row joined to its user, team,
 * organization role, and organization. Membership is managed from the team form,
 * so there is no create/update/delete here.
 */
class PersonController extends Controller
{
    public function index(Request $request, PersonFilters $filters): Response
    {
        $this->authorize('viewAny', Person::class);

        $people = $filters->apply(Person::query()->with(['user', 'team', 'role', 'organization']))
            ->keyset()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString();

        return Inertia::render('People/Index', [
            'people' => cursorResponse($people, fn (Person $person) => $this->row($person)),
            'filters' => $filters->echoBack(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(Person $person): array
    {
        return [
            'token' => $person->token,
            'name' => $person->user->name,
            'team' => $person->team->name,
            'role' => $person->role->name,
            'organization' => $person->organization->name,
            'created_at' => $person->created_at?->toIso8601String(),
        ];
    }
}
