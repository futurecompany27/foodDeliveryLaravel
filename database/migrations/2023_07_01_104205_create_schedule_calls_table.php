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
        Schema::create('schedule_calls', function (Blueprint $table) {
            $table->id();
            $table->string('chef_id');
            $table->string('date');
            $table->string('slot');
            $table->tinyinteger('status')->default(1)->comment('1- Pending, 0-Call Made, 2-No Response');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('schedule_calls');
    }
};
