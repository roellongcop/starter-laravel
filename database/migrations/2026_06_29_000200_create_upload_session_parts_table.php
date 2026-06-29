<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One row per durably-stored chunk. The unique (session, part_number)
        // makes a re-uploaded chunk idempotent (ON CONFLICT) and avoids the
        // lost-update races a JSON map on the parent row would suffer under
        // parallel PUTs. received_bytes/received_chunks are derived from here.
        Schema::create('upload_session_parts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('upload_session_id')->constrained('upload_sessions')->cascadeOnDelete();
            $table->unsignedInteger('part_number');
            $table->string('etag')->nullable();
            $table->unsignedBigInteger('size');
            $table->timestamps();

            $table->unique(['upload_session_id', 'part_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_session_parts');
    }
};
