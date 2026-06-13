<?php

namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['fixed', 'percentage']);
        $value = $type === 'fixed' ? $this->faker->randomFloat(2, 1, 50) : $this->faker->randomFloat(2, 5, 50);

        return [
            'code' => strtoupper($this->faker->unique()->lexify('SAVE???')),
            'description' => $this->faker->optional()->sentence(),
            'discount_type' => $type,
            'discount_value' => $value,
            'max_discount' => $type === 'percentage' ? $this->faker->randomFloat(2, 5, 100) : null,
            'min_order_amount' => $this->faker->randomFloat(2, 0, 50),
            'usage_limit' => $this->faker->numberBetween(1, 1000),
            'per_user_limit' => $this->faker->numberBetween(1, 10),
            'first_time_only' => $this->faker->boolean(20),
            'one_time_per_use' => $this->faker->boolean(30),
            'start_date' => now()->subDays(rand(0, 5)),
            'end_date' => now()->addDays(rand(5, 60)),
            'status' => 'active',
        ];
    }
}
