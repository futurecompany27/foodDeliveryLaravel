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
        Schema::table('adminsettings', function (Blueprint $table) {
            $table->integer('base_price')->nullable()->comment('base price for orders related to each chef per kilometer');
            $table->integer('min_shipping_charges')->nullable()->comment('minimun shipping charges for orders related to each chef');
            $table->integer('chef_max_income')->nullable()->comment('maximum income value for non_taxable chef');
            $table->integer('driver_max_income')->nullable()->comment('maximum income value for non_taxable driver');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adminsettings', function (Blueprint $table) {
            $table->dropColumn('base_price');
            $table->dropColumn('min_shipping_charges');
            $table->dropColumn('chef_max_income');
            $table->dropColumn('driver_max_income');
        });
    }
};

