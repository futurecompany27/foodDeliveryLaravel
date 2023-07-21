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
        Schema::create('chef_reviews', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('chef_id');
            $table->string('images');
            $table->unsignedInteger('star_rating');
            $table->string('message');
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
