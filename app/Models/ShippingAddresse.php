<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShippingAddresse extends Model
{
    use HasFactory;
    protected $table = 'shipping_addresses';
    protected $fillable = [
        'user_id',
        'firstName',
        'lastName',
        'mobile_no',
        'postal_code',
        'city',
        'state',
        'landmark',
        'latitude',
        'longitude',
        'locality',
        'full_address',
        'address_type',
        'default_address',
    ];

    public function orders()
    {
        return $this->belongsTo(Order::class, 'user_id', 'user_id');
    }
}
