<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequestForUpdateDetails extends Model
{
    use HasFactory, SoftDeletes;
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

    public function chef()
    {
        return $this->belongsTo(Chef::class, 'chef_id', 'id');
    }
}
