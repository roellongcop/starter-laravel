<?php

use App\Enums\TaskStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A requirement is a deliverable attached to a task. project_id/milestone_id/
 * organization_id are denormalized from the owning task (task → milestone →
 * project) so tagging (syncDataTags) and org-scoped queries never need to join
 * up the chain. minimum_files/maximum_files bound how many files satisfy it; it
 * carries its own workflow status, mirroring the task's TaskStatus.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requirements', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();
            $table->foreignId('task_id')->constrained('tasks')->cascadeOnDelete();
            $table->unsignedInteger('minimum_files')->nullable();
            $table->unsignedInteger('maximum_files')->nullable();
            $table->foreignId('reference_file_id')->nullable()->constrained('reference_files')->nullOnDelete();
            $table->foreignId('form_id')->nullable()->constrained('forms')->nullOnDelete();
            $table->string('status')->default(TaskStatus::Pending->value);
            $table->unsignedInteger('position')->default(0);
            $table->auditColumns();
            $table->index(['task_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('requirements');
    }
};
