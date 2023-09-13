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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('mobileNo');
            $table->text('password');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('are_you_a');
            $table->string('full_address');
            $table->string('address_proof')->nullable();
            $table->string('province');
            $table->string('city');
            $table->string('postal_code');
            $table->string('driving_licence_no')->nullable();
            $table->string('driving_licence_proof')->nullable();
            $table->string('taxation_no')->nullable();
            $table->string('taxation_proof')->nullable();
            $table->string('criminal_report')->nullable();
            $table->timestamp('status')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};