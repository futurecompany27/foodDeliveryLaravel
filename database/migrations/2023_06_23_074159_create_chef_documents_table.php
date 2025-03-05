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
        Schema::create('chef_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chef_id');
            $table->unsignedBigInteger('document_field_id')->comment('field dynamicaly generated with respect to the document');
            $table->string('field_value');
            $table->foreign('chef_id')->references('id')->on('chefs')->onDelete('NO ACTION');
            $table->foreign('document_field_id')->references('id')->on('document_item_fields')->onDelete('NO ACTION');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chef_documents');
    }
};
