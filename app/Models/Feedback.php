<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;
    protected $table = "feedbacks";
    protected $fillable = ['id', 'profile_pic', 'radio', 'name', 'email', 'profession', 'message', 'star_rating'];
}
