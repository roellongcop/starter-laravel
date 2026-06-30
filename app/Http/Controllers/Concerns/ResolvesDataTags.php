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
