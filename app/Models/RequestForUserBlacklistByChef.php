<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForUserBlacklistByChef extends Model
{
    use HasFactory;
    protected $fillable = [
        'chef_id',
        'user_id',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }

    public function reviews()
    {
        return $this->hasMany(ChefReview::class, 'user_id', 'user_id');
    }

}