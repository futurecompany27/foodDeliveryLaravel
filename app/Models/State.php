<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    use HasFactory;
    protected $fillable = ['country_id', 'name', 'status', 'tax_type', 'tax_value'];
    protected $casts = [
        'tax_type' => 'array',
        'tax_value' => 'array',
    ];
    public function cities()
    {
        return $this->hasMany(City::class); //fk on City  Model is state_id
    }

    public function country()
    {
        return $this->belongsTo(Country::class); //fk on state  Model is country_id
    }
}
