<?php

use App\Enums\BackupStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('backups', function (Blueprint $table): void {
            $table->id();
            $table->string('filename')->nullable();
            $table->string('disk')->default('backups');
            $table->unsignedBigInteger('size')->nullable();
            $table->string('status')->default(BackupStatus::Pending->value);
            $table->auditColumns();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('backups');
    }
};
