<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('request_for_user_blacklist_by_chefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chef_id');
            $table->unsignedBigInteger('user_id');
            $table->tinyInteger('status')->default(0)->comment('0 - pending, 1 - Request accepted, 2 - Request declined ');
            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('request_for_user_blacklist_by_chefs');
    }
};