<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeatingInstruction extends Model
{
    use HasFactory;
    protected $table = "heating_instructions";
    protected $fillable = ['title', 'description'];
}
