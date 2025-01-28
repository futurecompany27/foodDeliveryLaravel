<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChefReview extends Model
{
    use HasFactory;
    protected $table = "chef_reviews";
    protected $fillable = ['id', 'user_id', 'chef_id', 'star_rating', 'message'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }
}
