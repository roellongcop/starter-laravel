<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('themes', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->string('preview_image')->nullable();
            $table->json('tokens')->nullable();
            $table->boolean('is_default')->default(false);
            $table->auditColumns();

            $table->index('is_default');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('themes');
    }
};
