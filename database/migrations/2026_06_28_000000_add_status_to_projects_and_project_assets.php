<?php

use App\Enums\ProjectStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->string('status')->default(ProjectStatus::Pending->value)->after('private');
        });

        Schema::table('project_assets', function (Blueprint $table): void {
            // Per-binding workflow status; new attachments start Pending.
            $table->string('status')->default(ProjectStatus::Pending->value)->after('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table): void {
            $table->dropColumn('status');
        });

        Schema::table('project_assets', function (Blueprint $table): void {
            $table->dropColumn('status');
        });
    }
};
