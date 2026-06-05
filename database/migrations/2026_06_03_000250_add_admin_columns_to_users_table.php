<?php

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin/domain columns on the framework users table. Runs after files so the
 * avatar_file_id foreign key resolves.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('password_hint')->nullable()->after('password');
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('user_status')->default(UserStatus::Active->value)->after('password_hint');
            $table->string('avatar')->nullable()->after('user_status');
            // Avatar image: references the backing File (a URL fallback lives in
            // `avatar`). Nulled if that file is deleted.
            $table->foreignId('avatar_file_id')->nullable()->after('avatar')
                ->constrained('files')->nullOnDelete();

            // Unguessable route-binding key (see HasToken); resource URLs expose
            // this instead of the enumerable auto-increment id.
            $table->uuid('token')->unique()->after('id');

            // Audit footer (users already has created_at/updated_at). The keyset
            // index lives in the companion add_keyset_index migration so it can
            // match the auditColumns() composite (record_status, created_at, id).
            $table->unsignedTinyInteger('record_status')->default(RecordStatus::Active->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('avatar_file_id');
            $table->dropUnique(['token']);
            $table->dropColumn([
                'token',
                'password_hint',
                'username',
                'user_status',
                'avatar',
                'record_status',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
