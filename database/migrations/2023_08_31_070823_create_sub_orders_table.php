<?php

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
        Schema::create('sub_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->foreign('order_id')->references('order_id')->on('orders')->onDelete('cascade');
            $table->string('sub_order_id')->nullable()->unique();
            $table->string('chef_id');
            $table->string('track_id')->nullable()->unique();
            $table->string('item_total');
            $table->string('amount');
            $table->string('tip')->nullable();
            $table->string('tip_type');
            $table->string('tip_amount');
            $table->string('status')->default('Pending');
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
