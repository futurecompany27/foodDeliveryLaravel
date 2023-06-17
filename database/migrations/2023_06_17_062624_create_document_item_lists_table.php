<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('document_item_lists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->string('document_item_name');
            $table->string('chef_type');
            $table->string('reference_links')->nullable();
            $table->string('additional_links')->nullable();
            $table->string('detail_information')->nullable();
            $table->string('status')->default(0)->comment('0- inactive 1- active');
            $table->foreign('state_id')->references('id')->on('states')->onDelete('NO ACTION');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_item_lists');
    }
};