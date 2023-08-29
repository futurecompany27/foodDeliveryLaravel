<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FoodItemReview extends Model
{
    use HasFactory;
    protected $fillable = ['food_id', 'user_id', 'rating', 'review', 'reviewImages'];
    protected $casts = [
        'reviewImages' => 'array'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
    public function food()
    {
        return $this->belongsTo(FoodItem::class, 'food_id', 'id');
    }
}