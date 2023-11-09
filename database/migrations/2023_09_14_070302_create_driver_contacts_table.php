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
        Schema::create('driver_contacts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger("driver_id");
            $table->String("subject");
            $table->text("message");
            $table->tinyinteger('status')->default(1)->comment('1-Pending 0-Replied');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_contacts');
    }
};
