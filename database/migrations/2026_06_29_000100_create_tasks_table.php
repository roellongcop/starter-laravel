<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasks', function (Blueprint $table): void {
            // A task is a card inside a milestone. organization_id is denormalized
            // from the project so syncDataTags() can org-scope tags without a join.
            // Assignment FKs null out when a user is deleted; position drives the
            // draggable card order within a milestone.
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('assigned_to_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('observer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('private')->default(false);
            $table->date('due_date')->nullable();
            $table->foreignId('reference_file_id')->nullable()->constrained('reference_files')->nullOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->auditColumns();
            $table->index(['milestone_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
