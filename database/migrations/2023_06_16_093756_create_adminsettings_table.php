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
            $table->integer('default_comm')->default(0.0)->nullable();
            $table->integer('refugee_comm')->default(0.0)->nullable();
            $table->integer('singlemom_comm')->default(0.0)->nullable();
            $table->integer('lostjob_comm')->default(0.0)->nullable();
            $table->integer('student_comm')->default(0.0)->nullable();
            $table->integer('food_default_comm')->default(0.0)->nullable();
            $table->integer('radius')->default(1)->comment("shef find with in this range to customer");
            $table->integer('radiusForDriver')->default(5);
            $table->integer('multiChefOrderAllow')->default(5)->comment("user can add multi chef order if its under this range");
            $table->longText('Work_with_us_content')->nullable();
            $table->softDeletes();
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
