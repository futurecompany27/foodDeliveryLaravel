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
        Schema::create('payment_credentials_card_data', function (Blueprint $table) {
            $table->id();
            $table->string('user_id');
            $table->string('card_holder_name');
            $table->string('card_number');
            $table->string('expiry_date');
            $table->string('cvv');
            $table->tinyInteger('isDefault')->default(0)->comment('0 - inactive, 1- active');
            $table->tinyInteger('isParentIsDefault')->default(0)->comment('0 - inactive, 1- active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_credentials_card_data');
    }
};