<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodCategory extends Model
{
    use HasFactory;
    protected $fillable = ['category', 'image', 'commission'];
    public function sub_category()
    {
        return $this->hasMany(FoodSubcategory::class);
    }
}