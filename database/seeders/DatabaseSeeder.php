<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            AdminSettingSeeder::class,
            AllergiesSeeder::class,
            BankNameSeeder::class,
            DietarysSeeder::class,
            CountrySeeder::class,
            StatesSeeder::class,
            citySeeeder::class,
            postalCodeSeeeder::class,
            DriverSeeder::class,
            UserSeeder::class,
            // ChefSeeder::class,
            DocumetItemListSeeder::class,
            DocumetItemFieldsSeeder::class,
            FoodCategorySeeder::class,
            // FoodItemSeeder::class,
            HeatingInstructionSeeder::class,
            IngredientSeeder::class,
            KitchenTypeSeeder::class,
            ShefTypeSeeder::class,
            ShefSubTypeSeeder::class,
            SitesettingSeeder::class,
            UnitSeeder::class,
            taxSeeeder::class,
            OrderStatusSeeder::class,
            \App\Models\Coupon::factory()->count(10)->create(),
            \Database\Seeders\CouponSeeder::class
        ]);
    }
}
