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
        Schema::create('chef_alternative_contact', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chef_id');
            $table->string('mobile');
            $table->string('status')->default(1)->comment('1-active, 0-inactive');
            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('cascade');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_alternative_contact');
    }
};
