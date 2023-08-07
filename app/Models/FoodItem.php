<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItem extends Model
{
    use HasFactory;
    protected $table = 'food_items';

    protected $fillable = [
        'chef_id',
        'dish_name',
        'description',
        'dishImage',
        'dishImageThumbnail',
        'regularDishAvailabilty',
        'from',
        'to',
        'foodAvailibiltyOnWeekdays',
        'orderLimit',
        'foodTypeId',
        'spicyLevel',
        'geographicalCuisine',
        'otherCuisine',
        'ingredients',
        'otherIngredients',
        'allergies',
        'dietary',
        'heating_instruction_id',
        'heating_instruction_description',
        'package',
        'size',
        'expiresIn',
        'serving_unit',
        'serving_person',
        'price',
        'comments',
        'approved_status',
        'approvedAt'
    ];

    protected $casts = [
        'foodAvailibiltyOnWeekdays' => 'array',
        'geographicalCuisine' => 'array',
        'otherCuisine' => 'array',
        'ingredients' => 'array',
        'otherIngredients' => 'array',
        'allergies' => 'array',
        'dietary' => 'array',
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }

    public function category()
    {
        return $this->belongsTo(FoodCategory::class, 'foodTypeId', 'id');
    }

    public function heatingInstruction()
    {
        return $this->belongsTo(HeatingInstruction::class, 'heating_instruction_id', 'id');
    }
}