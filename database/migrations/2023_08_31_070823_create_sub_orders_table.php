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
            $table->double('chef_commission',8,2);
            $table->double('chef_commission_amount',8,2)->nullable();
            $table->json('chef_commission_taxes')->nullable();
            $table->json('chef_sale_taxes')->nullable();
            $table->double('chef_earning', 8,2)->nullable();
            $table->double('driver_commission',8,2)->comment('Driver Comm %');
            $table->double('driver_commission_amount',8,2)->comment('Driver Comm Amount');
            $table->json('driver_commission_taxes')->comment('Driver Comm Tax');
            $table->json('sub_order_tax_detail')->nullable();
            $table->string('track_id')->nullable()->unique();
            $table->string('item_total');
            $table->string('amount');
            $table->unsignedInteger('tip')->nullable();
            $table->string('tip_type');
            $table->unsignedDouble('tip_amount', 8, 2);
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
