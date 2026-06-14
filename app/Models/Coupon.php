<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_type',
        'discount_value',
        'max_discount',
        'min_order_amount',
        'usage_limit',
        'per_user_limit',
        'first_time_only',
        'one_time_per_use',
        'start_date',
        'end_date',
        'status'
    ];
}
