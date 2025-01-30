<?php

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->unique();
            $table->json('tax_types');
            $table->double('order_total', 8,2);
            $table->double('order_tax', 8,2);
            $table->string('order_date')->default(Carbon::now()->toDateTimeString());
            $table->string('shipping')->default(0);
            $table->string('shipping_tax')->default(0);
            $table->string('discount_amount')->default(0);
            $table->double('grand_total', 8,2);
            $table->string('user_id');
            $table->string('shipping_address');
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('payment_mode');
            $table->string('delivery_date');
            $table->string('delivery_time');
            $table->string('food_instruction')->nullable();
            $table->string('delivery_option')->nullable();
            $table->string('option_desc')->nullable();
            $table->string('delivery_instructions')->nullable();
            $table->string('payment_status');
            $table->string('transacton_id')->nullable();
            $table->string('total_order_item');
            $table->string('tip_total');
            $table->string('user_mobile_no');
            $table->string('username');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
