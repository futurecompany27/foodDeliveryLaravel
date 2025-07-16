<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\CouponUsage;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'discount_type', 'discount_value', 'max_discount',
        'min_order_amount', 'usage_limit', 'per_user_limit', 'first_time_only',
        'one_time_per_user', 'start_date', 'end_date', 'status'
    ];

    public function usages()
    {
        return $this->hasMany(CouponUsage::class);
    }
}
