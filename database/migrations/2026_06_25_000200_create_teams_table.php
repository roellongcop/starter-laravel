<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            // Lookups are restricted on delete so a category/role still in use
            // can't be removed out from under its teams.
            $table->foreignId('team_category_id')->constrained('team_categories')->restrictOnDelete();
            $table->foreignId('organization_role_id')->constrained('organization_roles')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->auditColumns();
            // A team name is unique within its organization (may repeat across orgs).
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teams');
    }
};
