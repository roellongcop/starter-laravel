<?php

namespace App\Models\Concerns;

use App\Models\DataTag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

/**
 * Makes a model taggable with org-scoped DataTags through the polymorphic
 * `taggables` pivot. Tags belong to an organization, so a model may only carry
 * tags from its own organization — enforced in syncDataTags() (and mirrored by
 * the frontend picker + request validation).
 *
 * @property-read Collection<int, DataTag> $tags
 *
 * @phpstan-require-extends Model
 */
trait HasDataTags
{
    /**
     * Detach a model's tags when it is deleted (the polymorphic side cannot
     * cascade through a database foreign key).
     */
    public static function bootHasDataTags(): void
    {
        static::deleting(function (Model $model): void {
            /** @var self $model */
            $model->tags()->detach();
        });
    }

    /**
     * @return MorphToMany<DataTag, $this>
     */
    public function tags(): MorphToMany
    {
        return $this->morphToMany(DataTag::class, 'taggable');
    }

    /**
     * Replace this model's tags with the DataTags identified by the given
     * tokens, keeping only those belonging to the model's own organization.
     *
     * @param  array<int, string>  $tokens
     */
    public function syncDataTags(array $tokens): void
    {
        $ids = DataTag::query()
            ->where('organization_id', $this->getAttribute('organization_id'))
            ->whereIn('token', $tokens)
            ->pluck('id')
            ->all();

        $this->tags()->sync($ids);
    }
}
