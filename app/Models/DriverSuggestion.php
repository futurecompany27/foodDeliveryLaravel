<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverSuggestion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['id', 'driver_id' ,'related_to', 'message', 'sample_pic'];


    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }

}
