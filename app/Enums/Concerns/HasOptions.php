<?php

namespace App\Enums\Concerns;

use Illuminate\Support\Str;

/**
 * Shared helpers for backed enums: list values/names, derive a human label from
 * the case name, and build {value,label} option arrays for React selects.
 */
trait HasOptions
{
    /** @return array<int, int|string> */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }

    /** @return array<int, string> */
    public static function names(): array
    {
        return array_map(fn (self $case) => $case->name, self::cases());
    }

    /** Human-readable label derived from the case name (e.g. RestoreFailed -> "Restore Failed"). */
    public function label(): string
    {
        return Str::headline($this->name);
    }

    /** @return array<int, array{value: int|string, label: string}> */
    public static function options(): array
    {
        return array_map(
            fn (self $case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases(),
        );
    }
}
