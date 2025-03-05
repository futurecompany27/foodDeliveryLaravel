<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodSubcategory extends Model
{
    use HasFactory;
    protected $fillable = ['food_category_id', 'name', 'status'];


    public function category()
    {
        return $this->belongsTo(FoodCategory::class);
    }
}
