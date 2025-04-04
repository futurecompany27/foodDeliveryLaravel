<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderTrackDetails extends Model
{
    use HasFactory;
    protected $fillable = [
        'track_id',
        'status',
        'track_desc',
        'date',
        'time'
    ];
}
