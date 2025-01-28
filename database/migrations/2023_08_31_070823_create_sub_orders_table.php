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
        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->string('sub_order_id')->nullable()->unique();
            $table->string('chef_id');
            $table->float('chef_commission',10,2);
            $table->float('chef_commission_amount',10,2)->nullable();
            $table->json('chef_commission_taxes')->nullable();
            $table->json('chef_sale_taxes')->nullable();
            $table->float('driver_commission',10,2)->comment('Driver Comm %');
            $table->float('driver_commission_amount',10,2)->nullable()->comment('Driver Comm Amount');
            $table->json('driver_commission_taxes')->nullable()->comment('Driver Comm Tax');
            $table->json('sub_order_tax_detail')->nullable();
            $table->string('track_id')->nullable()->unique();
            $table->string('item_total');
            $table->string('amount');
            $table->string('tip')->nullable();
            $table->string('tip_type');
            $table->string('tip_amount');
            $table->string('status')->default('2');
            $table->text('reason')->nullable();
            $table->unsignedInteger('driver_id')->nullable();
            $table->string('pickup_token')->nullable();
            $table->string('customer_delivery_token')->nullable();
            $table->string('delivery_proof_img')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sub_orders');
    }
};
