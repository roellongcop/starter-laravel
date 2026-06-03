<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            // References the File whose media backs the user's avatar. Nulled if
            // that file is deleted. The legacy `avatar` string column is kept as
            // a URL fallback.
            $table->foreignId('avatar_file_id')
                ->nullable()
                ->after('avatar')
                ->constrained('tbl_files')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('avatar_file_id');
        });
    }
};
