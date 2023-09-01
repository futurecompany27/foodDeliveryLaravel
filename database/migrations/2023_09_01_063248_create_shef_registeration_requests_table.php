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
        Schema::create('shef_registeration_requests', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('date_of_birth');
            $table->string('email')->unique();
            $table->string('mobile')->unique();
            $table->text("address_line")->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->text("state")->nullable();
            $table->text("city")->nullable();
            $table->string('postal_code');
            $table->json("kitchen_types")->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shef_registeration_requests');
    }
};