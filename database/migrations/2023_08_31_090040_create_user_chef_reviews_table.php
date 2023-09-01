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
        Schema::create('user_chef_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('star_rating');
            $table->unsignedBigInteger('chef_id');
            $table->unsignedBigInteger('user_id');
            $table->string('message');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->tinyinteger('status')->default(0)->comment('1-completed 0-pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_chef_reviews');
    }
};
