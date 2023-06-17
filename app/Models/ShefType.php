<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShefType extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'status'
    ];

    public function subtype()
    {
        return $this->hasMany(ShefSubtype::class);
    }
}
