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
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->string('images');
            $table->string('are_you_a');
            $table->string('name');
            $table->string('email');
            $table->string('profession');
            $table->text('message');
            $table->tinyinteger('status')->default(1)->comment('1-approved 0-unapproved');
            $table->unsignedInteger('star_rating');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('feedbacks');
    }
};
