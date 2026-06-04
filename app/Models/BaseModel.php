<?php

namespace App\Models;

use App\Enums\RecordStatus;
use App\Models\Concerns\Blameable;
use App\Models\Concerns\HasRecordStatus;
use App\Models\Concerns\HasToken;
use App\Models\Concerns\IsResource;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable;

/**
 * Base for every domain model. Composes the shared resource plumbing
 * (IsResource: table prefix, keyset ordering, config()) with blameable
 * stamping, the record_status lifecycle, and the audit trail.
 *
 * Timestamps are stored in UTC (config('app.timezone') = 'UTC'); display-time
 * conversion is a presentation concern (SystemSettings, Phase 4).
 */
abstract class BaseModel extends Model implements Auditable
{
    use AuditableTrait;
    use Blameable;
    use HasRecordStatus;
    use HasToken;
    use IsResource;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'record_status' => RecordStatus::class,
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}
