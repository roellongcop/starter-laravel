<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `users` table predates the auditColumns() macro, which adds a
 * (created_at, id) composite index to every other domain table for keyset
 * (cursor) pagination. Without it, the users list does a full filesort on
 * `ORDER BY created_at DESC, id DESC` — slow once the table has tens of
 * thousands of rows. Add the matching index here.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['created_at', 'id']);
        });
    }
};
