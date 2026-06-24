<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_responses', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->foreignId('form_id')->constrained('forms')->cascadeOnDelete();
            // Map of field id => submitted answer value.
            $table->json('answers')->nullable();
            // created_by (from auditColumns/Blameable) is the respondent.
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_responses');
    }
};
