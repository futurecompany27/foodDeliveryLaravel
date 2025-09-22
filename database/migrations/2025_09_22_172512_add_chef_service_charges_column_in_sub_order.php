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
        Schema::table('sub_orders', function (Blueprint $table) {
            $table->double('chef_service_charge_amount', 10, 2)->nullable()->after('chef_commission_taxes');
            $table->longText('chef_service_charge_taxes')->nullable()->after('chef_service_charge_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sub_orders', function (Blueprint $table) {
            $table->dropColumn(['chef_service_charge_amount', 'chef_service_charge_taxes']);
        });
    }
};
