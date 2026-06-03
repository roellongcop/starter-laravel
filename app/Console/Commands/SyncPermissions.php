<?php

namespace App\Console\Commands;

use App\Support\Permissions;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Reconciles the spatie permissions table with the declared registry in
 * config/permissions.php. Idempotent; run at seed/deploy time — never per
 * request. Authorization must come from declared permissions, not route
 * reflection.
 */
class SyncPermissions extends Command
{
    protected $signature = 'permissions:sync {--prune : Delete permissions no longer in the registry}';

    protected $description = 'Create/refresh permissions from config/permissions.php';

    public function handle(PermissionRegistrar $registrar): int
    {
        $guard = (string) config('permissions.guard', 'web');
        $declared = Permissions::all();

        $created = 0;
        foreach ($declared as $name) {
            $permission = Permission::findOrCreate($name, $guard);
            if ($permission->wasRecentlyCreated) {
                $created++;
            }
        }

        $pruned = 0;
        if ($this->option('prune')) {
            $stale = Permission::where('guard_name', $guard)
                ->whereNotIn('name', $declared)
                ->get();

            foreach ($stale as $permission) {
                $permission->delete();
                $pruned++;
            }
        }

        $registrar->forgetCachedPermissions();

        $this->info(sprintf(
            'Permissions synced: %d total (%d new%s).',
            count($declared),
            $created,
            $this->option('prune') ? ", {$pruned} pruned" : '',
        ));

        return self::SUCCESS;
    }
}
