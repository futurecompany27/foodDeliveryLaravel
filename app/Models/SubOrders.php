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

    public function Orders()
    {
        return $this->belongsTo(Order::class, 'order_id', 'order_id');
    }
    
    public function OrderItems(){
        return $this->hasMany(OrderItems::class, 'sub_order_id', 'sub_order_id');
    }

    public function OrderTrack(){
        return $this->hasMany(OrderTrackDetails::class, 'track_id', 'track_id');
    }

    public function chefs()
    {
        return $this->belongsTo(chef::class, 'chef_id', 'id');
    }
}