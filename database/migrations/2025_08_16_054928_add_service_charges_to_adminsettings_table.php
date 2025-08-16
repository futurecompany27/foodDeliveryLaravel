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
            $table->integer('chef_service_charges')->default(0)->nullable()->comment('Service charges for chefs');
            $table->integer('driver_service_charges')->default(0)->nullable()->comment('Service charges for drivers');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adminsettings', function (Blueprint $table) {
            $table->dropColumn(['chef_service_charges', 'driver_service_charges']);
        });
    }
};
