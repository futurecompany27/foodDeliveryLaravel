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
        'first_name',
        'last_name',
        'mobile_no',
        'postal_code',
        'city',
        'state',
        'landmark',
        'locality',
        'full_address',
        'address_type',
        'default_address',
    ];
}
