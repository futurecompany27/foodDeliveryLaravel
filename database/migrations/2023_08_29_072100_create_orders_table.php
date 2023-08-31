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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->comment('in this the order id will be stored in HP000001 format');
            $table->json('tax_types');
            $table->string('order_total');
            $table->string('order_tax');
            $table->string('order_date');
            $table->string('shipping');
            $table->string('shipping_tax');
            $table->string('discount_amount');
            $table->string('discount_tax');
            $table->string('grand_total');
            $table->string('user_id');
            $table->string('shipping_address');
            $table->string('city');
            $table->string('state');
            $table->string('landmark');
            $table->string('postal_code');
            $table->string('lat');
            $table->string('long');
            $table->string('payment_mode');
            $table->string('delivery_date');
            $table->string('from_time');
            $table->string('to_time');
            $table->string('payment_status');
            $table->string('transacton_id');
            $table->string('total_order_item');
            $table->string('tip_total');
            $table->string('token');
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