<?php

use App\Models\Chef;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('food_licenses', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Chef::class)->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status',[0,1,2,3,4,5])->default('0')->comment('Status: 0->verification_pending, 1->verified by homeplate, 2->submited to govt, 3->issued by govt, 4->rejected by govt, 5->wrong data submited');
            $table->string('flag')->nullable();
            $table->string('business_name')->nullable();
            $table->string('business_mobile')->nullable();
            $table->string('civic_number')->nullable();
            $table->text('street_name')->nullable();
            $table->text('city')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('vehicle_number')->nullable();
            $table->date('start_date')->nullable();
            $table->string('owner_name')->nullable();
            $table->string('company_name')->nullable();
            $table->string('enterprise_number')->nullable();
            $table->string('company_mobile')->nullable();
            $table->string('applicant_civic_number')->nullable();
            $table->string('applicant_street_name')->nullable();
            $table->string('applicant_city')->nullable();
            $table->string('applicant_postal_code')->nullable();
            $table->string('applicant_province')->nullable();
            $table->string('applicant_country')->nullable();
            $table->string('catering_general')->nullable();
            $table->string('catering_hot_cold')->nullable();
            $table->string('catering_buffet')->nullable();
            $table->string('catering_maintaining')->nullable();
            $table->string('retail_general')->nullable();
            $table->string('retail_maintaining')->nullable();
            $table->string('annual_rate')->nullable();
            $table->string('additional_unit')->nullable();
            $table->string('total_unit')->nullable();
            $table->string('total_amount')->nullable();
            $table->string('facility_dedicated')->nullable();
            $table->string('sink_area_premises')->nullable();
            $table->string('potable_water_access')->nullable();
            $table->string('regulatory_dispenser')->nullable();
            $table->string('recovery_evacuation')->nullable();
            $table->string('ventilation_system')->nullable();
            $table->string('waste_container')->nullable();
            $table->string('manager_name')->nullable();
            $table->string('manager_number')->nullable();
            $table->string('applicant_name')->nullable();
            $table->string('signature')->nullable();
            $table->date('declaration_date')->nullable();
            $table->text('message')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('food_licenses');
    }
};
