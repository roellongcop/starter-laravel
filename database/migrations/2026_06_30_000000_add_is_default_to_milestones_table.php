<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('milestones', function (Blueprint $table): void {
            // The auto-seeded "Misc" milestone every project-asset board starts
            // with — flagged so it can't be renamed or deleted.
            $table->boolean('is_default')->default(false)->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('milestones', function (Blueprint $table): void {
            $table->dropColumn('is_default');
        });
    }
};
