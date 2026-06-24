<?php

namespace App\Http\Controllers;

use App\Filters\AssetFilters;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    public function index(Request $request, AssetFilters $filters): Response
    {
        $this->authorize('viewAny', Asset::class);

        $assets = $filters->apply(Asset::query()->with('organization'))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Asset $asset) => $this->row($asset));

        return Inertia::render('Assets/Index', [
            // Inertia::scroll() merges (appends) the paginator's `data` wrapper on
            // partial reloads, driving the <InfiniteScroll> card grid; cursor
            // metadata is derived from the CursorPaginator automatically.
            'assets' => Inertia::scroll($assets),
            'filters' => $filters->echoBack(),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $this->authorize('create', Asset::class);

        Asset::create($this->resolveOrganization($request->validated()));

        return redirect()->route('assets.index')->with('success', 'Asset created.');
    }

    public function show(Asset $asset): Response
    {
        $this->authorize('view', $asset);

        return Inertia::render('Assets/Show', [
            'asset' => $this->row($asset->load('organization')),
            'organizations' => $this->organizationOptions(),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $asset->update($this->resolveOrganization($request->validated()));

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        return redirect()->route('assets.index')->with('success', 'Asset deleted.');
    }

    /**
     * Translate the organization token into its id (never trust ids from the
     * frontend — only tokens cross the wire).
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
     * Selectable organizations for the picker, keyed by token.
     *
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
    protected function row(Asset $asset): array
    {
        return [
            'token' => $asset->token,
            'name' => $asset->name,
            'id_code' => $asset->id_code,
            'address' => $asset->address,
            'organization' => $asset->organization->token,
            'organization_name' => $asset->organization->name,
            'record_status' => $asset->record_status->value,
            'created_at' => $asset->created_at?->toIso8601String(),
        ];
    }
}
