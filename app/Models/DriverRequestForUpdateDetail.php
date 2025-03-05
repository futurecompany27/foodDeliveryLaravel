<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DriverRequestForUpdateDetail extends Model
{
    use HasFactory, SoftDeletes;
    protected $table = 'driver_request_for_update_details';
    protected $fillable = [
        "driver_id",
        'request_for',
        'message',
        'status'
    ];

    protected $casts = [
        'request_for' => 'array'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }
}
