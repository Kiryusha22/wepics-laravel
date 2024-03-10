<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tag_image', function (Blueprint $table) {
            $table->foreignId('image_id')
                ->references('id')
                ->on('images')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('tag_id')
                ->references('id')
                ->on('tags')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->primary(['image_id', 'tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tag_image');
    }
};
