<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assets', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->string('id_code');
            $table->string('address');
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->auditColumns();
            // An asset name is unique within its organization (may repeat across orgs).
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};
