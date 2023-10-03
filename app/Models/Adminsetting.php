<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Adminsetting extends Model
{
    use HasFactory;
    protected $table = "adminsettings";
    protected $fillable = ['id', 'default_comm', 'refugee_comm', 'singlemom_comm', 'lostjob_comm', 'student_comm', 'food_default_comm', 'radius', 'multiChefOrderAllow'];
}