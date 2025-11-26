<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->decimal('shipping_tax_gst', 10, 2)->nullable()->after('shipping_tax');
            $table->decimal('shipping_tax_qst', 10, 2)->nullable()->after('shipping_tax_gst');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_tax_gst');
            $table->dropColumn('shipping_tax_qst');
        });
    }
};
