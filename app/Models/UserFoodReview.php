<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFoodReview extends Model
{
    use HasFactory;
    protected $table = "user_food_reviews";
    protected $fillable = ['id', 'foodimage', 'chef_id', 'user_id', 'food_id', 'star_rating', 'message', 'status'];
    protected $casts = [
        'foodimage' => 'array'
    ];

    public function chef()
    {
        return $this->belongsTo(Chef::class);
    }
    public function food()
    {
        return $this->belongsTo(FoodItem::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
