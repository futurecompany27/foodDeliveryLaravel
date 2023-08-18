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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('fullname');
            $table->string('mobile')->unique()->nullable();
            $table->string('mobile_verified_at')->nullable();
            $table->string('email')->unique();
            $table->text('password')->nullable();
            $table->string('social_id')->nullable();
            $table->string('social_type')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->tinyinteger('status')->default(1)->comment('1 - active, 0 - inactive');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};