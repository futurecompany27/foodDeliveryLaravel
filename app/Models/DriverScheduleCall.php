<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverScheduleCall extends Model
{
    use HasFactory;
    protected $table = "driver_schedule_calls";
    protected $fillable = ['driver_id', 'date', 'slot', 'status'];

    public function driver() {
        return $this->belongsTo(Driver::class, 'driver_id','id');
    }
}
