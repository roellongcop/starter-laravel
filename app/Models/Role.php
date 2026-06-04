<?php

namespace App\Models;

use App\Enums\RoleType;
use App\Models\Concerns\HasToken;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Extends the spatie Role with the admin metadata the app needs:
 * - role_type      System (seeded, protected) vs Custom (user-defined)
 * - module_access  { resourceKey: [abilities] } — drives button visibility in React
 * - main_navigation sidebar tree rendered in React
 *
 * No HasRecordStatus global scope here: spatie resolves roles through this model
 * during permission checks, and a hidden-by-default scope could mask grants.
 *
 * @property string $token
 * @property RoleType|null $role_type
 * @property string|null $description
 * @property array<string, array<int, string>>|null $module_access
 * @property array<int, mixed>|null $main_navigation
 * @property int $priority
 */
class Role extends SpatieRole
{
    use HasToken;

    protected $fillable = [
        'name',
        'guard_name',
        'role_type',
        'description',
        'module_access',
        'main_navigation',
        'priority',
        'record_status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'role_type' => RoleType::class,
            'module_access' => 'array',
            'main_navigation' => 'array',
            'priority' => 'integer',
        ];
    }
}
