<?php

use App\Enums\UploadStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('upload_sessions', function (Blueprint $table): void {
            $table->id();
            $table->uuid('token')->unique();
            $table->string('original_name');
            $table->string('extension', 32)->nullable();
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->unsignedBigInteger('chunk_size');
            $table->unsignedInteger('total_chunks');

            // The `uploads` driver in use when the session began — authoritative
            // for the session's life, so a UPLOADS_DISK_DRIVER flip mid-upload
            // can't route complete() to the wrong backend.
            $table->string('driver');

            // S3 multipart upload id (null on the local driver) and the canonical
            // Y/m/<random>.ext key the assembled object lands at — chosen at init
            // so finalize can write the File/Media rows pointing straight at it.
            $table->string('s3_upload_id')->nullable();
            $table->string('object_key');

            $table->string('status')->default(UploadStatus::Pending->value);
            $table->text('error_message')->nullable();
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tag')->nullable();
            $table->foreignId('file_id')->nullable()->constrained('files')->nullOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->auditColumns();

            $table->index('owner_id');
            $table->index('status');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('upload_sessions');
    }
};
