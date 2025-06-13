<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('chef_stories', function (Blueprint $table) {
            $table->unsignedBigInteger('chef_id')->primary();
            $table->text('experience')->nullable();
            $table->string('file')->nullable();
            $table->timestamps();

            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('chef_stories');
    }
};
