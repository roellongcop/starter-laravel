<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reference_files', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            // The single attached file; nulled if the underlying file is removed.
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->auditColumns();
            // A reference name is unique within its organization (may repeat across orgs).
            $table->unique(['organization_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reference_files');
    }
};
