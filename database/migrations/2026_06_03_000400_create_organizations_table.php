<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->foreignId('point_of_contact_id')->nullable()->constrained('users')->nullOnDelete();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
