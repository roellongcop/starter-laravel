<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('milestones', function (Blueprint $table): void {
            // A milestone is a board column scoped to one project's view of an
            // asset (the project_assets binding). Both FKs cascade so the board is
            // cleaned when either the project or the asset is deleted; position
            // drives the draggable column order.
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->unsignedInteger('position')->default(0);
            $table->auditColumns();
            $table->index(['project_id', 'asset_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('milestones');
    }
};
