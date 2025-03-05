<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kitchentype extends Model
{
    use HasFactory;
    protected $table = "kitchentypes";
    protected $fillable = ['kitchentype','image', 'status'];
}
