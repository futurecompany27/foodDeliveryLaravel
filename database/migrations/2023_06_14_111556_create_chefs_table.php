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
        Schema::create('chefs', function (Blueprint $table) {
            $table->id();
            $table->enum('is_hfc_paid', [0,1])->default('0')->comment('status: 0->unpaid, 1->paid');
            $table->enum('is_rrc_paid', [0,1])->default('0')->comment('status: 0->unpaid, 1->paid');
            $table->tinyInteger('is_taxable')->default('0')->comment('taxable: 0->not taxable, 1->taxable');
            $table->string('firstName');
            $table->string('lastName');
            $table->string('date_of_birth');
            $table->string('type')->nullable();
            $table->string('sub_type')->nullable();
            $table->text("address_line1")->nullable();
            $table->text("address_line2")->nullable();
            $table->text("state")->nullable();
            $table->text("city")->nullable();
            $table->string('postal_code');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('mobile')->unique();
            $table->string('profile_pic')->nullable();
            $table->tinyInteger("is_mobile_verified")->default(0)->comment("0 - not verified 1 - verified");
            $table->string('email')->unique();
            $table->tinyInteger("is_email_verified")->default(0)->comment("0 - not verified 1 - verified");
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password')->nullable();
            $table->string("is_personal_detail_complete")->default(0)->comment("0 - incomplete, 1 - complete");
            $table->string("address_proof")->nullable();
            $table->string("address_proof_path")->nullable();
            $table->string("id_proof_1")->nullable();
            $table->string("id_proof_path1")->nullable();
            $table->string("id_proof_2")->nullable();
            $table->string("id_proof_path2")->nullable();
            $table->string("are_you_a")->nullable()->comment("student/refugee/single mom/lost job");
            $table->string("are_you_a_file_path")->nullable();
            $table->string("twitter_link")->nullable();
            $table->string("facebook_link")->nullable();
            $table->string("tiktok_link")->nullable();
            $table->string("kitchen_name")->nullable();
            $table->string("chef_banner_image")->nullable();
            $table->string("chef_card_image")->nullable();
            $table->text("kitchen_types")->nullable();
            $table->text("other_kitchen_types")->nullable();
            $table->text("about_kitchen")->nullable();
            $table->string("gst_no")->nullable();
            $table->string("qst_no")->nullable();
            $table->string("gst_image")->nullable();
            $table->string("qst_image")->nullable();
            $table->string("bank_name")->nullable();
            $table->string("transit_number")->nullable();
            $table->string("account_number")->nullable();
            $table->string("institution_number")->nullable();
            $table->decimal("rating", 2, 1)->default(0);
            $table->tinyinteger("new_to_canada")->default(0)->comment('1 - Yes, 0 - No');
            $table->tinyinteger('status')->default(0)->comment('1-Active,0-Inactive,2-Inreview');
            $table->tinyinteger('chefAvailibilityStatus')->default(1)->comment('1-Available,2-Unavailable');
            $table->json('chefAvailibilityWeek')->nullable();
            $table->string('chefAvailibilityFromTime')->nullable();
            $table->string('chefAvailibilityToTime')->nullable();
            $table->json('blacklistedUser')->nullable();
            $table->text('resetToken')->nullable();
            $table->tinyInteger('profilePercenatge')->default(0);
            $table->longText('story')->nullable();
            $table->string('story_img')->nullable();
            $table->tinyInteger('is_personal_details_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_special_benefit_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_document_details_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_fhc_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_rrc_certificate_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_bank_detail_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_social_detail_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_kitchen_detail_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->tinyInteger('is_tax_document_completed')->default('0')->comment('status: 0->incomplete, 1->complete');
            $table->rememberToken();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('chefs');
    }
};
