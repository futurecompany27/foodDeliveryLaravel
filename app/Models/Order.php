<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_number',
        'tax_types',
        'order_total',
        'order_tax',
        'order_date',
        'shipping',
        'shipping_tax',
        'discount_amount',
        'discount_tax',
        'grand_total',
        'user_id',
        'shipping_address',
        'city',
        'state',
        'landmark',
        'postal_code',
        'latitude',
        'longitude',
        'payment_mode',
        'delivery_date',
        'from_time',
        'to_time',
        'food_instruction',
        'delivery_option',
        'option_desc',
        'delivery_instructions',
        'payment_status',
        'transacton_id',
        'total_order_item',
        'tip_total',
        'user_mobile_no',
        'username',
        'token'
    ];

    protected $casts = [
        'tax_types' => 'array',
    ];

    public function subOrders()
    {
        return $this->hasMany(SubOrders::class, 'order_id', 'order_id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'user_id','id');
    }
    public function chef(){
        return $this->belongsTo(Chef::class, 'user_id','id');
    }
}
