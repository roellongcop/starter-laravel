<?php

namespace App\Support;

/**
 * Sanitizes image-resize query params to a bounded set (widths snap to a preset
 * ladder, fit whitelisted) so the Glide cache can't be flooded with arbitrary
 * sizes. See docs/features/files-and-media.md.
 */
class ImageParams
{
    /** @var list<int> */
    public const WIDTHS = [32, 64, 128, 200, 400, 800];

    public const DEFAULT_WIDTH = 400;

    /**
     * @param  array<string, mixed>  $query
     * @return array<string, int|string>
     */
    public static function sanitize(array $query): array
    {
        $params = ['w' => self::nearestWidth((int) ($query['w'] ?? 0))];

        $fit = is_string($query['fit'] ?? null) ? $query['fit'] : null;
        if (in_array($fit, ['crop', 'contain', 'max'], true)) {
            $params['fit'] = $fit;

            // fit modes need a height; default to a square of the chosen width.
            $h = (int) ($query['h'] ?? 0);
            $params['h'] = $h > 0 ? min($h, max(self::WIDTHS)) : $params['w'];
        }

        return $params;
    }

    public static function nearestWidth(int $requested): int
    {
        if ($requested <= 0) {
            return self::DEFAULT_WIDTH;
        }

        foreach (self::WIDTHS as $width) {
            if ($requested <= $width) {
                return $width;
            }
        }

        return max(self::WIDTHS);
    }
}
