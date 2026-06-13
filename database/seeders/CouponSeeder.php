<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // create sample coupons
        Coupon::factory()->count(10)->create()->each(function ($coupon) {
            // maybe create a few usages for some coupons
            if (rand(0, 1)) {
                CouponUsage::factory()->count(rand(1, 5))->create(['coupon_id' => $coupon->id]);
            }
        });
    }
}
