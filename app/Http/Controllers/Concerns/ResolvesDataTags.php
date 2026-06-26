<?php

namespace App\Http\Controllers\Concerns;

use App\Models\DataTag;
use Illuminate\Database\Eloquent\Collection;

/**
 * Shared helpers for controllers whose resource is taggable: the org-scoped tag
 * picker options and the per-row tag serialization. Only tokens cross the wire.
 */
trait ResolvesDataTags
{
    /**
     * Selectable tags for the picker, each carrying its organization token so the
     * frontend can show only the tags belonging to the chosen organization.
     *
     * @return array<int, array{value: string, label: string, organization: string, color: string}>
     */
    protected function dataTagOptions(): array
    {
        return DataTag::query()
            ->with('organization')
            ->orderBy('name')
            ->get()
            ->map(fn (DataTag $tag): array => [
                'value' => $tag->token,
                'label' => $tag->name,
                'organization' => $tag->organization->token,
                'color' => $tag->color,
            ])
            ->all();
    }

    /**
     * Serialize an attached-tags collection into frontend chips (token/name/color).
     *
     * @param  Collection<int, DataTag>  $tags
     * @return array<int, array{token: string, name: string, color: string}>
     */
    protected function serializeTags(Collection $tags): array
    {
        return $tags
            ->map(fn (DataTag $tag): array => [
                'token' => $tag->token,
                'name' => $tag->name,
                'color' => $tag->color,
            ])
            ->values()
            ->all();
    }
}
