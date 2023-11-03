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
        Schema::create('shipping_addresses', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('mobile_no');
            $table->string('postal_code');
            $table->string('city')->nullable();
            $table->string('state');
            $table->string('landmark')->nullable();
            $table->string('locality')->nullable();
            $table->string('full_address');
            $table->string('address_type');
            $table->string('default_address')->default(0)->comment('1 - default, 0 - not default');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_addresses');
    }
};
