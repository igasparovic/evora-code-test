<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGamesTable extends Migration
{
    public function up()
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('p1Name');
            $table->string('p2Name');
            $table->text('board');
            $table->text('bag');
            $table->text('p1Rack');
            $table->text('p2Rack');
            $table->integer('turnCount')->default(1);
            $table->string('winner')->nullable();
            $table->integer('p1Score')->default(0);
            $table->integer('p2Score')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('games');
    }
}
