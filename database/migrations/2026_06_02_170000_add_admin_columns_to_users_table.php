<?php

use App\Enums\RecordStatus;
use App\Enums\UserStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->nullable()->unique()->after('email');
            $table->string('user_status')->default(UserStatus::Active->value)->after('password_hint');
            $table->string('avatar')->nullable()->after('user_status');

            // Audit footer (users already has created_at/updated_at).
            $table->unsignedTinyInteger('record_status')->default(RecordStatus::Active->value);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->index('record_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex(['record_status']);
            $table->dropColumn([
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
