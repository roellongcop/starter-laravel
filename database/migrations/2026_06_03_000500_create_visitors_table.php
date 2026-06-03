<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tbl_visitors', function (Blueprint $table): void {
            $table->id();
            $table->string('cookie_id')->unique();
            $table->string('ip_address', 45)->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->string('device')->nullable();
            $table->string('session_id')->nullable();
            $table->unsignedInteger('visit_count')->default(0);
            $table->timestamp('last_visit_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tbl_visitors');
    }
};
