<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table): void {
            // A polymorphic pivot attaching a DataTag to any taggable model
            // (assets, forms, reference files, projects). Deleting a tag drops
            // its attachments; the HasDataTags trait detaches on entity delete.
            $table->foreignId('data_tag_id')->constrained('data_tags')->cascadeOnDelete();
            $table->morphs('taggable'); // taggable_type, taggable_id
            $table->unique(['data_tag_id', 'taggable_id', 'taggable_type'], 'taggables_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taggables');
    }
};
