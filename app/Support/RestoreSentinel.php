<?php

namespace App\Support;

/**
 * File-based "restore in progress" sentinel (a file, not the DB-backed cache, so
 * it survives a DB restore). Read by EnforceRestoreMode.
 * See docs/features/backups-exports-imports.md.
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
