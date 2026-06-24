<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\FormFactory;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * A form belonging to an organization, with an ordered array of field
 * definitions stored as JSON (`form_fields`). Submissions live in FormResponse.
 *
 * @property string $token
 * @property string $title
 * @property string|null $description
 * @property array<int, array<string, mixed>>|null $form_fields
 * @property int $organization_id
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Organization $organization
 * @property-read Collection<int, FormResponse> $responses
 * @property-read int|null $responses_count
 */
class Form extends BaseModel
{
    /** @use HasFactory<FormFactory> */
    use HasFactory;

    protected $fillable = ['title', 'description', 'form_fields', 'organization_id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'form_fields' => 'array',
        ]);
    }

    /**
     * @return BelongsTo<Organization, $this>
     */
    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * @return HasMany<FormResponse, $this>
     */
    public function responses(): HasMany
    {
        return $this->hasMany(FormResponse::class);
    }
}
