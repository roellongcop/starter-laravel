<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Asset;

/**
 * Shared asset serialization + picker options. Keeping the row shape in one place
 * means an asset rendered inside another resource (e.g. a project's attached
 * assets) stays identical to the Assets module and updates when the asset does.
 * Requires the using controller to also expose serializeTags() (ResolvesDataTags).
 */
trait SerializesAssets
{
    use ResolvesDataTags;

    /**
     * @return array<string, mixed>
     */
    protected function assetRow(Asset $asset): array
    {
        return [
            'token' => $asset->token,
            'name' => $asset->name,
            'id_code' => $asset->id_code,
            'address' => $asset->address,
            'organization' => $asset->organization->token,
            'organization_name' => $asset->organization->name,
            'tags' => $this->serializeTags($asset->tags),
            'record_status' => $asset->record_status->value,
            'created_at' => $asset->created_at?->toIso8601String(),
        ];
    }

    /**
     * Selectable assets for an attach picker, scoped to one organization and keyed
     * by token (ids never cross the wire).
     *
     * @return array<int, array{value: string, label: string}>
     */
    protected function assetOptions(int $organizationId): array
    {
        return Asset::query()
            ->where('organization_id', $organizationId)
            ->orderBy('name')
            ->get(['token', 'name'])
            ->map(fn (Asset $asset): array => ['value' => $asset->token, 'label' => $asset->name])
            ->all();
    }
}
