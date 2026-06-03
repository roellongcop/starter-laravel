<?php

namespace App\Models;

use Database\Factories\ThemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Carbon;

/**
 * A light/dark token palette. `tokens` mirrors app.css:
 * { "light": { "--background": "0 0% 100%", … }, "dark": { … } }.
 * Exactly one theme is the default; its tokens are injected app-wide.
 *
 * @property array<string, array<string, string>>|null $tokens
 * @property bool $is_default
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Theme extends BaseModel
{
    /** @use HasFactory<ThemeFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'preview_image',
        'tokens',
        'is_default',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_merge(parent::casts(), [
            'tokens' => 'array',
            'is_default' => 'boolean',
        ]);
    }
}
