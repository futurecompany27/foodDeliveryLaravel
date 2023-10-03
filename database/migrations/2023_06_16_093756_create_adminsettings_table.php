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
        Schema::create('adminsettings', function (Blueprint $table) {
            $table->id();
            $table->decimal('default_comm')->default(0.0)->nullable();
            $table->decimal('refugee_comm')->default(0.0)->nullable();
            $table->decimal('singlemom_comm')->default(0.0)->nullable();
            $table->decimal('lostjob_comm')->default(0.0)->nullable();
            $table->decimal('student_comm')->default(0.0)->nullable();
            $table->decimal('food_default_comm')->default(0.0)->nullable();
            $table->decimal('radius')->default(1)->comment("shef find with in this range to customer");
            $table->decimal('multiChefOrderAllow')->default(5)->comment("user can add multi chef order if its under this range");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adminsettings');
    }
};
