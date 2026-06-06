<?php

use App\Enums\UserExportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_exports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('format', 10)->default('csv');
            $table->string('resource')->default('users');
            $table->json('filters')->nullable();
            $table->unsignedBigInteger('row_count')->nullable();
            // Progress for sharded exports: total_rows is set up front, shards
            // bump processed_rows so the grid can show a live bar. row_count stays
            // the final authoritative count written on completion.
            $table->unsignedBigInteger('total_rows')->nullable();
            $table->unsignedBigInteger('processed_rows')->default(0);
            $table->string('filename')->nullable();
            $table->string('status')->default(UserExportStatus::Pending->value);
            $table->text('error_message')->nullable();
            $table->auditColumns();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_exports');
    }
};
