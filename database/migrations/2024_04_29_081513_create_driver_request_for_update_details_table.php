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
        Schema::create('driver_request_for_update_details', function (Blueprint $table) {
            $table->id();
            $table->string('driver_id');
            $table->json('request_for');
            $table->string('message');
            $table->tinyInteger('status')->default(0)->comment('0 - pending, 1 - approved, 2 - completed');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_request_for_update_details');
    }
};
