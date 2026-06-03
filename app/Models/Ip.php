<?php

namespace App\Models;

use App\Enums\IpListType;
use App\Enums\RecordStatus;
use Database\Factories\IpFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

/**
 * An IP allow/deny entry consumed by the EnforceIpRules middleware.
 *
 * @property IpListType $list_type
 * @property RecordStatus $record_status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Ip extends BaseModel
{
    /** @use HasFactory<IpFactory> */
    use HasFactory;

    protected $fillable = ['ip_address', 'list_type', 'description'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'list_type' => IpListType::class,
        ]);
    }

    /**
     * Adds the resource-specific `white_list` process (flip rows to the
     * whitelist) on top of the default active/in_active/delete dispatcher.
     *
     * @param  array<int, int|string>  $ids
     */
    public static function bulkAction(string $process, array $ids): int
    {
        if ($process === 'white_list') {
            $query = static::query()->withInactive();

            return $query->whereIn($query->getModel()->getKeyName(), $ids)->update([
                'list_type' => IpListType::Whitelist->value,
                'updated_by' => Auth::id(),
                'updated_at' => now(),
            ]);
        }

        return parent::bulkAction($process, $ids);
    }
}
