<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChefAlternativeContact extends Model
{
    use HasFactory;
    protected $table = 'chef_alternative_contact';
    protected $fillable = [
        'chef_id',
        'mobile',
        'status',
    ];
}
