<?php

use App\Enums\UserImportStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_imports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('token', 64)->unique();
            $table->string('resource')->default('users');
            $table->string('filename')->nullable();
            $table->unsignedBigInteger('total')->default(0);
            $table->unsignedBigInteger('success')->default(0);
            $table->unsignedBigInteger('failed')->default(0);
            $table->string('error_report_path')->nullable();
            $table->string('status')->default(UserImportStatus::Pending->value);
            $table->auditColumns();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_imports');
    }
};
