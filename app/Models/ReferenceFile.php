<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\HasDataTags;
use Database\Factories\ReferenceFileFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A named reference belonging to an organization, with an optional single
 * attached file (an uploaded File on the private `uploads` disk).
 *
 * @property string $token
 * @property string $name
 * @property string|null $description
 * @property int $organization_id
 * @property int|null $file_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read File|null $file
 * @property-read Collection<int, DataTag> $tags
 */
class ReferenceFile extends BaseModel
{
    use HasDataTags;

    /** @use HasFactory<ReferenceFileFactory> */
    use HasFactory;

    protected $fillable = ['name', 'description', 'organization_id', 'file_id'];

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return BelongsTo<File, $this>
     */
    public function file(): BelongsTo
    {
        return $this->belongsTo(File::class);
    }
}
