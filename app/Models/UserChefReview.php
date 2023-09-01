<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserChefReview extends Model
{
    use HasFactory;
    protected $table = "user_chef_reviews";
    protected $fillable = ['id', 'chef_id', 'user_id', 'star_rating', 'message', 'status'];

    public function chef()
    {
        return $this->belongsTo(chef::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
