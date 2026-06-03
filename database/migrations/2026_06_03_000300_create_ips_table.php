<?php

use App\Enums\IpListType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ips', function (Blueprint $table): void {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('list_type')->default(IpListType::Blacklist->value);
            $table->string('description')->nullable();
            $table->auditColumns();

            $table->index(['list_type', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ips');
    }
};
