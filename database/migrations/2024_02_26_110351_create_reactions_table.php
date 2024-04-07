<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('reactions', function (Blueprint $table) {
           $table->id();
           $table->string('value',4)
               ->charset('utf8mb4')
               ->collation('utf8mb4_bin')
               ->unique();
           $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reactions');
    }
};
