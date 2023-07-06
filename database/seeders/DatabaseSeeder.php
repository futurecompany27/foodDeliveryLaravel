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
            BankNameSeeder::class,
            CountrySeeder::class,
            FoodCategorySeeder::class,
            HeatingInstructionSeeder::class,
            IngredientSeeder::class,
            KitchenTypeSeeder::class,
            UnitSeeder::class,
            NutritionsSeeder::class,
            DietarysSeeder::class,
            ChefSeeder::class
        ]);
    }
}