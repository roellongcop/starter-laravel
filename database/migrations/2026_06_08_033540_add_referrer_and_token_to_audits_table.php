<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Project-owned additions to owen-it's published audits table — kept in a separate
 * migration so the package's create_audits_table migration stays untouched.
 *
 * - referrer: the page a request came from (HTTP Referer). `url` records the endpoint
 *   that was hit; for background uploads (e.g. POST /documents from a form) that is all
 *   `url` shows, so the referrer reveals the originating page.
 * - token: unguessable route-binding key (see HasToken); log URLs expose this instead
 *   of the enumerable auto-increment id.
 */
return new class extends Migration
{
    public function up(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->text('referrer')->nullable()->after('url');
            $table->uuid('token')->unique()->after('id');
        });
    }

    public function down(): void
    {
        $connection = config('audit.drivers.database.connection', config('database.default'));
        $table = config('audit.drivers.database.table', 'audits');

        Schema::connection($connection)->table($table, function (Blueprint $table) {
            $table->dropUnique(['token']);
            $table->dropColumn(['token', 'referrer']);
        });
    }
};
