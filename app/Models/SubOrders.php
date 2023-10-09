<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubOrders extends Model
{
    use HasFactory;
    protected $fillable = [
        'order_id',
        'sub_order_id',
        'chef_id',
        'track_id',
        'item_total',
        'amount',
        'tip',
        'tip_type',
        'tip_amount',
        'status'
    ];
}