<?php

namespace App\Models;

use App\Enums\RecordStatus;
use Database\Factories\FormResponseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A single submission of a Form. `answers` maps each field id to its submitted
 * value; the respondent is the Blameable `created_by` user.
 *
 * @property string $token
 * @property int $form_id
 * @property array<string, mixed>|null $answers
 * @property int|null $created_by
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Form $form
 * @property-read User|null $creator
 */
class FormResponse extends BaseModel
{
    /** @use HasFactory<FormResponseFactory> */
    use HasFactory;

    protected $fillable = ['form_id', 'answers'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'answers' => 'array',
        ]);
    }

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
