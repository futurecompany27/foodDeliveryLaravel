<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Dietary extends Model
{
    use HasFactory;
    protected $table = 'dietaries';
    protected $fillable = ['diet_name', 'diet_image', 'small_description'];
}