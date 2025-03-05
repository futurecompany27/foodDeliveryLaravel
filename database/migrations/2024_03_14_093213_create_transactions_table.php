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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type');
            $table->string('user_type');
            $table->integer('user_id');
            $table->float('amount', 10, 2);
            $table->text('remark')->nullable();
            $table->text('payment_log')->nullable();
            $table->string('payment_id')->nullable();
            $table->string('txn_no');
            $table->string('status')->nullable()->comment('it can be done, pending, inprocess');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
