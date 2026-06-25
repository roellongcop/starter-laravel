<?php

namespace App\Http\Controllers;

use App\Filters\DataTagFilters;
use App\Http\Requests\StoreDataTagRequest;
use App\Http\Requests\UpdateDataTagRequest;
use App\Models\DataTag;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DataTagController extends Controller
{
    public function index(Request $request, DataTagFilters $filters): Response
    {
        $this->authorize('viewAny', DataTag::class);

        $tags = $filters->apply(DataTag::query()->with('organization'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (DataTag $tag) => $this->row($tag));

        return Inertia::render('DataTags/Index', [
            'dataTags' => Inertia::scroll($tags),
            'filters' => $filters->echoBack(),
            'organizations' => $this->organizationOptions(),
            'colors' => DataTag::COLORS,
        ]);
    }

    public function store(StoreDataTagRequest $request): RedirectResponse
    {
        $this->authorize('create', DataTag::class);

        DataTag::create($this->resolveOrganization($request->validated()));

        return redirect()->route('data-tags.index')->with('success', 'Tag created.');
    }

    public function show(DataTag $dataTag): Response
    {
        $this->authorize('view', $dataTag);

        return Inertia::render('DataTags/Show', [
            'dataTag' => $this->row($dataTag->load('organization')),
            'organizations' => $this->organizationOptions(),
            'colors' => DataTag::COLORS,
        ]);
    }

    public function update(UpdateDataTagRequest $request, DataTag $dataTag): RedirectResponse
    {
        $this->authorize('update', $dataTag);

        $dataTag->update($this->resolveOrganization($request->validated()));

        return back()->with('success', 'Tag updated.');
    }

    public function destroy(DataTag $dataTag): RedirectResponse
    {
        $this->authorize('delete', $dataTag);

        $dataTag->delete();

        return redirect()->route('data-tags.index')->with('success', 'Tag deleted.');
    }

    /**
     * Translate the organization token into its id (only tokens cross the wire).
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function resolveOrganization(array $data): array
    {
        $data['organization_id'] = Organization::where('token', $data['organization'])->value('id');
        unset($data['organization']);

        return $data;
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    protected function organizationOptions(): array
    {
        return Organization::query()
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (Organization $organization) => ['value' => $organization->token, 'label' => $organization->name])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    protected function row(DataTag $tag): array
    {
        return [
            'token' => $tag->token,
            'name' => $tag->name,
            'description' => $tag->description,
            'color' => $tag->color,
            'organization' => $tag->organization->token,
            'organization_name' => $tag->organization->name,
            'record_status' => $tag->record_status->value,
            'created_at' => $tag->created_at?->toIso8601String(),
        ];
    }
}
