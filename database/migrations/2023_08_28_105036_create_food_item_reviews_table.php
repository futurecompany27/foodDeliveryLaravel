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
        Schema::create('food_item_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_id');
            $table->foreign('food_id')->references('id')->on('food_items');
            $table->unsignedBigInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');
            $table->decimal("rating", 2, 1)->default(0);
            $table->text('review');
            $table->json('reviewImages');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_item_reviews');
    }
};
