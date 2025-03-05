<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverRejectOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        "driver_id",
    ];

    public function driver(){
        return $this->belongsTo(Driver::class, 'driver_id','id');
    }

    public function orders(){
        return $this->hasMany(Order::class, 'order_id','id');
    }


}
