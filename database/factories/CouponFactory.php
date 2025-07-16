<?php
namespace Database\Factories;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\Factory;

class CouponFactory extends Factory
{
    protected $model = Coupon::class;

    public function definition()
    {
        $type = $this->faker->randomElement(['fixed', 'percentage']);
        return [
            'code' => strtoupper($this->faker->unique()->lexify('COUPON????')),
            'description' => $this->faker->sentence,
            'discount_type' => $type,
            'discount_value' => $type === 'fixed' ? $this->faker->numberBetween(50, 500) : $this->faker->numberBetween(5, 50),
            'max_discount' => $type === 'percentage' ? $this->faker->numberBetween(100, 1000) : null,
            'min_order_amount' => $this->faker->numberBetween(100, 1000),
            'usage_limit' => $this->faker->numberBetween(10, 100),
            'per_user_limit' => $this->faker->numberBetween(1, 5),
            'first_time_only' => $this->faker->boolean,
            'one_time_per_user' => $this->faker->boolean,
            'start_date' => now()->subDays(5),
            'end_date' => now()->addDays(30),
            'status' => $this->faker->randomElement(['active', 'inactive']),
        ];
    }
}
