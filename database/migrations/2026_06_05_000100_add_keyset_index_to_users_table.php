<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The `users` table predates the auditColumns() macro. Mirror that macro's
 * keyset index here: the active global scope filters record_status, then the
 * list orders `created_at DESC, id DESC`. A composite (record_status,
 * created_at, id) serves the whole pattern from one index — no filesort, no
 * per-row filter — and also covers the onlyInactive() listing.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->index(['record_status', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['record_status', 'created_at', 'id']);
        });
    }
};
