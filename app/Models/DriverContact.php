<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverContact extends Model
{
    use HasFactory;
    protected $table = "driver_contacts";
    protected $fillable = ['driver_id', 'subject', 'message', 'status'];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }
}
