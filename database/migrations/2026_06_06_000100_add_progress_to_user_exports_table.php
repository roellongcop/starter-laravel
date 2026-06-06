<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_exports', function (Blueprint $table): void {
            // Progress for sharded exports: total_rows is set up front, shards
            // bump processed_rows so the grid can show a live bar. row_count stays
            // the final authoritative count written on completion.
            $table->unsignedBigInteger('total_rows')->nullable()->after('row_count');
            $table->unsignedBigInteger('processed_rows')->default(0)->after('total_rows');
        });
    }

    public function down(): void
    {
        Schema::table('user_exports', function (Blueprint $table): void {
            $table->dropColumn(['total_rows', 'processed_rows']);
        });
    }
};
