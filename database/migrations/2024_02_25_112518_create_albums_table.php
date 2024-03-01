<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAlbumsTable extends Migration
{
    public function up()
    {
        Schema::create('albums', function (Blueprint $table) {
            $table->id();
            $table->string('name', 127);
            $table->string('path',511);
            $table->string('hash', 255);
            $table->unsignedBigInteger('parent_album_id')->nullable();
            $table->timestamps();

            $table->foreign('parent_album_id')
                ->references('id')
                ->on('albums')
                ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('albums');
    }
}
