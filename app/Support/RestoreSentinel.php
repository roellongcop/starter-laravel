<?php

namespace App\Support;

/**
 * A file-based "restore in progress" sentinel. Deliberately a file (not the
 * cache, which is database-backed and would itself be overwritten during a DB
 * restore) so it survives the restore window. Read by EnforceRestoreMode.
 */
class RestoreSentinel
{
    protected static function path(): string
    {
        return storage_path('app/restore.lock');
    }

    public static function put(?int $operatorId): void
    {
        file_put_contents(self::path(), json_encode([
            'operator_id' => $operatorId,
            'started_at' => now()->toIso8601String(),
        ]));
    }

    public static function clear(): void
    {
        if (is_file(self::path())) {
            @unlink(self::path());
        }
    }

    public static function active(): bool
    {
        return is_file(self::path());
    }

    public static function operatorId(): ?int
    {
        if (! self::active()) {
            return null;
        }

        $data = json_decode((string) file_get_contents(self::path()), true);

        return isset($data['operator_id']) ? (int) $data['operator_id'] : null;
    }
}
