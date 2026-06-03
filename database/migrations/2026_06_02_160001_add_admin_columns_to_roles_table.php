<?php

use App\Enums\RecordStatus;
use App\Enums\RoleType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Extends spatie's roles table with the admin metadata the spec requires. The
 * roles table already has timestamps, so we add the rest of the audit footer
 * (record_status/created_by/updated_by) plus the role-specific columns.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->string('role_type')->default(RoleType::Custom->value)->after('guard_name');
            $table->string('description')->nullable()->after('role_type');
            $table->json('module_access')->nullable()->after('description');
            $table->json('main_navigation')->nullable()->after('module_access');
            // Higher priority wins when merging a multi-role user's custom menus.
            $table->unsignedSmallInteger('priority')->default(0)->after('main_navigation');

            $table->unsignedTinyInteger('record_status')->default(RecordStatus::Active->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->index('record_status');
        });
    }

    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropIndex(['record_status']);
            $table->dropColumn([
                'role_type',
                'description',
                'module_access',
                'main_navigation',
                'priority',
                'record_status',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
