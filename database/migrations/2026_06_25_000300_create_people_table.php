<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('people', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // The member's org-role, inherited from the team at add time.
            $table->foreignId('organization_role_id')->constrained('organization_roles')->restrictOnDelete();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->auditColumns();
            // Uniqueness is keyed on (team, user) ONLY — never on user or
            // (user, organization). This lets one user belong to teams in many
            // organizations (each with a different org-role) and to several teams
            // within one org; the same user just can't join the same team twice.
            $table->unique(['team_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('people');
    }
};
