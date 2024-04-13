<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tokens', function (Blueprint $table) {
            $table->string   ('value', 255)->unique();
            $table->foreignId('user_id');
            $table->timestamps();

            $table->primary(['value', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tokens');
    }
};
