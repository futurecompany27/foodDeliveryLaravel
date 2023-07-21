<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestForUpdateDetails extends Model
{
    use HasFactory;
    protected $table = 'request_for_update_details';
    protected $fillable = [
        "chef_id",
        'request_for',
        'message',
        'status'
    ];

    protected $casts = [
        'request_for' => 'array'
    ];
}