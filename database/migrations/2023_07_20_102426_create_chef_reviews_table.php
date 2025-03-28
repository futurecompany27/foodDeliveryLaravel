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
        Schema::create('chef_reviews', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('chef_id');
            $table->unsignedInteger('star_rating');
            $table->text('message');
            $table->tinyInteger('requestedForDeletion')->default(0)->comment('0 - Not requested, 1 - Requested for deletion');
            $table->tinyInteger('requestedForBlackList')->default(0)->comment('0 - Not requested, 1 - Requested for deletion');
            $table->tinyInteger('status')->default(1)->comment('1 - active, 2 - inactive');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_reviews');
    }
};