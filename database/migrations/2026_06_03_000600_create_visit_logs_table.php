<?php

use App\Enums\VisitLogAction;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('visitor_id')->constrained('visitors')->cascadeOnDelete();
            $table->string('url', 2048)->nullable();
            $table->string('action')->default(VisitLogAction::PageView->value);
            $table->json('data')->nullable();
            $table->auditColumns();

            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_logs');
    }
};
