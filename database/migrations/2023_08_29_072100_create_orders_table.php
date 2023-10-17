<?php

use Carbon\Carbon;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable()->unique();
            $table->json('tax_types');
            $table->string('order_total');
            $table->string('order_tax');
            $table->string('order_date')->default(Carbon::now()->toDateTimeString());
            $table->string('shipping')->default(0);
            $table->string('shipping_tax')->default(0);
            $table->string('discount_amount')->default(0);
            $table->string('grand_total');
            $table->string('user_id');
            $table->string('shipping_address');
            $table->string('city');
            $table->string('state');
            $table->string('postal_code');
            $table->string('payment_mode');
            $table->string('delivery_date');
            $table->string('delivery_time');
            $table->string('payment_status')->default(0);
            $table->string('transacton_id')->nullable();
            $table->string('total_order_item');
            $table->string('tip_total');
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
