<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A task's assignee/approver/observer move from a top-level user FK to a
 * polymorphic reference (Team|Person) scoped to the task's organization — so a
 * task is owned by someone inside the org, not an arbitrary system user. Existing
 * user-based assignments are dropped (they're incompatible with the new target).
 */
return new class extends Migration
{
    public function up(): void
    {
        // Drop the foreign keys before the columns — sqlite (the test DB) refuses
        // to drop a column still referenced by a FK definition.
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropForeign(['assigned_to_id']);
            $table->dropForeign(['approver_id']);
            $table->dropForeign(['observer_id']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropColumn(['assigned_to_id', 'approver_id', 'observer_id']);
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->nullableMorphs('assignee');
            $table->nullableMorphs('approver');
            $table->nullableMorphs('observer');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table): void {
            $table->dropMorphs('assignee');
            $table->dropMorphs('approver');
            $table->dropMorphs('observer');
        });

        Schema::table('tasks', function (Blueprint $table): void {
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete();
        });
    }
};
