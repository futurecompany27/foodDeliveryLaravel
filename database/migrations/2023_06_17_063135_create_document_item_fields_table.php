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
        Schema::create('document_item_fields', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('document_item_list_id')->nullable();
            $table->string('field_name');
            $table->string('type');
            $table->tinyInteger('mandatory')->default(0)->comment('0- not required , 1- required');
            $table->tinyInteger('allows_as_kitchen_name')->default(0)->comment('0- not applicable , 1- applicable');
            $table->foreign('document_item_list_id')->references('id')->on('document_item_lists')->onDelete('NO ACTION');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_item_fields');
    }
};
