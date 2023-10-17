<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItems extends Model
{
    use HasFactory;

    public function subOrder()
    {
        return $this->belongsTo(SubOrders::class, 'sub_order_id', 'sub_order_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'sub_order_id', 'sub_order_id');
    }

    public function foodItem()
    {
        return $this->belongsTo(FoodItem::class, 'food_id', 'id');
    }
}
