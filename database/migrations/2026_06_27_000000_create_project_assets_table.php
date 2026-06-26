<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_assets', function (Blueprint $table): void {
            // Pivot binding a project to existing org assets by reference only —
            // no attributes are copied, so renaming an asset reflects everywhere.
            // FK cascades clean the pivot when either side is deleted; the unique
            // pair makes attaching idempotent.
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('asset_id')->constrained('assets')->cascadeOnDelete();
            $table->unique(['project_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_assets');
    }
};
