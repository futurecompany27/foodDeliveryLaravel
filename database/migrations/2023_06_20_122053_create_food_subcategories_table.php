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
        Schema::create('food_subcategories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('food_category_id');
            $table->string('name');
            $table->string('status');
            $table->foreign('food_category_id')->references('id')->on('food_categories')->onDelete('NO ACTION');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_subcategories');
    }
};
