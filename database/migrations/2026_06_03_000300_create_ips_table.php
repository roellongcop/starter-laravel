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
            $table->uuid('token')->unique();
            // One row per IP: unique on ip_address also satisfies the
            // EnforceIpRules lookup, so no separate (list_type, ip_address) index.
            $table->string('ip_address', 45)->unique();
            $table->string('list_type')->default(IpListType::Blacklist->value);
            $table->string('description')->nullable();
            $table->auditColumns();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ips');
    }
};
