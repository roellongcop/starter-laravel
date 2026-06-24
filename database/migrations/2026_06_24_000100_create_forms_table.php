<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('forms', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            // Ordered array of field definitions (type + per-type config).
            $table->json('form_fields')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('forms');
    }
};
