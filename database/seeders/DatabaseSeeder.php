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
            AllergiesSeeder::class,
            BankNameSeeder::class,
            ChefSeeder::class,
            CountrySeeder::class,
            StatesSeeder::class,
            DietarysSeeder::class,
            DocumetItemListSeeder::class,
            DocumetItemFieldsSeeder::class,
            FoodCategorySeeder::class,
            HeatingInstructionSeeder::class,
            IngredientSeeder::class,
            KitchenTypeSeeder::class,
            ShefTypeSeeder::class,
            ShefSubTypeSeeder::class,
            SitesettingSeeder::class,
            UnitSeeder::class,
        ]);
    }
}