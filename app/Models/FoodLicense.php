<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodLicense extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'chef_id',
        'status',
        'flag',
        'business_name',
        'business_mobile',
        'civic_number',
        'street_name',
        'city',
        'postal_code',
        'vehicle_number',
        'start_date',
        'owner_name',
        'company_name',
        'enterprise_number',
        'company_mobile',
        'applicant_civic_number',
        'applicant_street_name',
        'applicant_city',
        'applicant_postal_code',
        'applicant_province',
        'applicant_country',
        'catering_general',
        'catering_hot_cold',
        'catering_buffet',
        'catering_maintaining',
        'retail_general',
        'retail_maintaining',
        'annual_rate',
        'facility_dedicated',
        'sink_area_premises',
        'potable_water_access',
        'regulatory_dispenser',
        'recovery_evacuation',
        'ventilation_system',
        'waste_container',
        'manager_name',
        'manager_number',
        'additional_unit',
        'total_unit',
        'total_amount',
        'applicant_name',
        'signature',
        'declaration_date',
        'message'

    ];


    public function chef()
    {
        return $this->belongsTo(Chef::class);
    }

}
