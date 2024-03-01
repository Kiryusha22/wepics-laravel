<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('reactions_pictures', function (Blueprint $table) {
            $table->timestamps();
            $table->foreignId('picture_id')
                ->references('id')
                ->on('pictures')
                ->onDelete('cascade');
            $table->foreignId('reaction_id')
                ->references('id')
                ->on('reactions')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reaction_picture');
    }
};
