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
        Schema::create('kitchentypes', function (Blueprint $table) {
            $table->id();
            $table->string('kitchentype');
            $table->string('image')->nullable();
            $table->tinyinteger('status')->default(1)->comment('1-active 0-inactive');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchentypes');
    }
};
