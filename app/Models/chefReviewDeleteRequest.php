<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class chefReviewDeleteRequest extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'chef_id', 'review_id'];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function chef()
    {
        return $this->belongsTo(chef::class, 'chef_id', 'id');
    }

    public function review()
    {
        return $this->belongsTo(ChefReview::class, 'review_id', 'id');
    }
}