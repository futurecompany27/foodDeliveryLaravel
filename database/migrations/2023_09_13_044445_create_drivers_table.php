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
        Schema::create('drivers', function (Blueprint $table) {
            $table->id();
            $table->text('profile_pic')->nullable();
            $table->string('firstName');
            $table->string('lastName');
            $table->string('email');
            $table->string('mobileNo');
            $table->text('password');
            $table->integer('is_email_varified')->default('0');
            $table->timestamp('email_verified_at')->nullable();
            $table->string('are_you_a');
            $table->string('full_address');
            $table->string('address_proof')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('province');
            $table->string('city');
            $table->string('postal_code');
            $table->string('driving_licence_no')->nullable();
            $table->string('driving_licence_proof')->nullable();
            $table->string("gst_no")->nullable();
            $table->string("qst_no")->nullable();
            $table->string("gst_image")->nullable();
            $table->string("qst_image")->nullable();
            $table->string('taxation_proof')->nullable();
            $table->string('criminal_report')->nullable();
            $table->string("bank_name")->nullable();
            $table->string("transit_number")->nullable();
            $table->string("account_number")->nullable();
            $table->string("institution_number")->nullable();
            $table->tinyinteger('status')->default(0);
            $table->tinyInteger('is_personal_details_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_driving_license_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_address_proof_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_tax_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_bank_document_detail')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->text('resetToken')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drivers');
    }
};
