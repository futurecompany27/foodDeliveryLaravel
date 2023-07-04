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
        Schema::create('food_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chef_id')->constrained('chefs')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('dish_name');
            $table->text('description');
            $table->string('dishImage');
            $table->string('dishImageThumbnail');
            $table->string('regularDishAvailabilty');
            $table->string('from')->nullable();
            $table->string('to')->nullable();
            $table->json('foodAvailibiltyOnWeekdays');
            $table->string('orderLimit');
            $table->foreignId('foodTypeId')->constrained('food_categories')->cascadeOnDelete()->cascadeOnUpdate();
            $table->string('spicyLevel');
            $table->json('geographicalCuisine')->nullable();
            $table->json('otherCuisine')->nullable();
            $table->json('ingredients')->nullable();
            $table->json('otherIngredients')->nullable();
            $table->json('nutritions')->nullable();
            $table->json('dietary')->nullable();
            $table->string('heating_instruction_id');
            $table->string('heating_instruction_description');
            $table->string('package');
            $table->string('size');
            $table->string('expiresIn');
            $table->string('serving_unit');
            $table->string('serving_person');
            $table->double('price');
            $table->string('comments');
            $table->string('status')->default('active');
            $table->string('approved_status')->default('pending');
            $table->string('approvedAt')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_items');
    }
};
