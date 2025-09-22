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
        Schema::table('orders', function (Blueprint $table) {
            $table->double('driver_commission', 10, 2)->nullable()->after('discount_amount');
            $table->double('driver_commission_amount', 10, 2)->nullable()->after('driver_commission');
            $table->longText('driver_commission_taxes')->nullable()->after('driver_commission_amount');
            $table->double('driver_service_charge_amount', 10, 2)->nullable()->after('driver_commission_taxes');
            $table->longText('driver_service_charge_taxes')->nullable()->after('driver_service_charge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['driver_commission', 'driver_commission_amount', 'driver_commission_taxes', 'driver_service_charge_amount', 'driver_service_charge_taxes']);
        });
    }
};
