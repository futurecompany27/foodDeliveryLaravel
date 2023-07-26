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
        Schema::create('states', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id');
            $table->string('name')->unqiue();
            $table->tinyInteger('status')->default(1)->comment('1-active , 0-inactive');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
            $table->string('tax_type')->nullable();
            $table->string('tax_value')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('states');
    }
};
