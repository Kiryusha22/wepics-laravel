<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reaction_image', function (Blueprint $table) {
            $table->foreignId('image_id')
                ->references('id')
                ->on('images')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('reaction_id')
                ->references('id')
                ->on('reactions')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->foreignId('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();
            $table->primary(['image_id', 'reaction_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reaction_image');
    }
};
