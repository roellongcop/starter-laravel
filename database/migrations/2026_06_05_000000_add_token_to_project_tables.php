<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds an unguessable UUID `token` to every project-owned table. The token is
 * the route-model-binding key (see HasToken), so resource URLs expose it instead
 * of the enumerable auto-increment id. user_exports/user_imports already ship a
 * token column, so they're omitted here.
 *
 * Library/framework tables are deliberately left untouched: notifications (UUID
 * pk), sessions (random hash), audits, settings, jobs, media, spatie pivots.
 */
return new class extends Migration
{
    /** @var list<string> */
    private array $tables = [
        'users',
        'user_meta',
        'roles',
        'files',
        'themes',
        'visitors',
        'visit_logs',
        'backups',
        'ips',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('token')->nullable()->after('id');
            });

            // Backfill existing rows (no-op on a fresh migrate — rows are seeded
            // afterwards and get their token from the HasToken creating hook).
            DB::table($table)->whereNull('token')->orderBy('id')->chunkById(500, function ($rows) use ($table): void {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->update(['token' => (string) Str::uuid()]);
                }
            });

            Schema::table($table, function (Blueprint $blueprint): void {
                $blueprint->uuid('token')->nullable(false)->unique()->change();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropUnique([$table.'_token_unique']);
                $blueprint->dropColumn('token');
            });
        }
    }
};
