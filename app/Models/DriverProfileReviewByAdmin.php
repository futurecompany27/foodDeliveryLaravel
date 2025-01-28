<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DriverProfileReviewByAdmin extends Model
{
    use HasFactory;

    protected $table = 'driver_profile_review_by_admin';
    protected $fillable = [
        'driver_id',
        'remark',
        'status'
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class, 'driver_id', 'id');
    }
}
