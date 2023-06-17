<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pincode extends Model
{
    use HasFactory;
    protected $fillable = ['city_id', 'pincode', 'status'];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}