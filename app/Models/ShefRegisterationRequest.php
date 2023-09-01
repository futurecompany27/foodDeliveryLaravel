<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShefRegisterationRequest extends Model
{
    use HasFactory;
    protected $casts = [
        'kitchen_types' => 'array',
    ];
}