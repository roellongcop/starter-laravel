<?php

namespace App\Http\Controllers;

use App\Filters\AssetFilters;
use App\Http\Controllers\Concerns\ProvidesOptions;
use App\Http\Controllers\Concerns\SerializesAssets;
use App\Http\Requests\StoreAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Models\Asset;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssetController extends Controller
{
    use ProvidesOptions;
    use SerializesAssets;

    public function index(Request $request, AssetFilters $filters): Response
    {
        $this->authorize('viewAny', Asset::class);

        $assets = $filters->apply(Asset::query()->with(['organization', 'tags']))
            ->keysetByToken()
            ->cursorPaginate(config('keen.pagination_size'))
            ->withQueryString()
            ->through(fn (Asset $asset) => $this->assetRow($asset));

        return Inertia::render('Assets/Index', [
            // Inertia::scroll() merges (appends) the paginator's `data` wrapper on
            // partial reloads, driving the <InfiniteScroll> card grid; cursor
            // metadata is derived from the CursorPaginator automatically.
            'assets' => Inertia::scroll($assets),
            'filters' => $filters->echoBack(),
        ]);
    }

    public function options(Request $request): JsonResponse
    {
        return $this->optionsResponse(
            $request,
            Asset::class,
            fn (Asset $asset): array => ['value' => $asset->token, 'label' => $asset->name],
            organizationScoped: true,
        );
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $this->authorize('create', Asset::class);

        $data = $this->resolveOrganization($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $asset = Asset::create($data);
        $asset->syncDataTags($tags);

        return back(fallback: route('assets.index'))->with('success', 'Asset created.');
    }

    public function show(Asset $asset): Response
    {
        $this->authorize('view', $asset);

        return Inertia::render('Assets/Show', [
            'asset' => $this->assetRow($asset->load(['organization', 'tags'])),
        ]);
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $this->authorize('update', $asset);

        $data = $this->resolveOrganization($request->validated());
        $tags = $data['tags'] ?? [];
        unset($data['tags']);

        $asset->update($data);
        $asset->syncDataTags($tags);

        return back()->with('success', 'Asset updated.');
    }

    public function destroy(Asset $asset): RedirectResponse
    {
        $this->authorize('delete', $asset);

        $asset->delete();

        return back(fallback: route('assets.index'))->with('success', 'Asset deleted.');
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
}
