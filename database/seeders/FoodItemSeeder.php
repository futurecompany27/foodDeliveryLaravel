<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FoodItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('food_items')->insert([
            'chef_id' => 1,
            'dish_name' => 'samosa',
            'description' => 'ye samose ka description hai',
            'dishImage' => 'http://127.0.0.1:8000/storage/foodItem/samosa.jpeg',
            'dishImageThumbnail' => 'http://127.0.0.1:8000/storage/foodItem/thumbnail/samosa.jpeg',
            'regularDishAvailabilty' => 'No Limits',
            "foodAvailibiltyOnWeekdays" => json_encode(['Su', 'M']),
            'orderLimit' => "10",
            'foodTypeId' => '1',
            'spicyLevel' => 'Mild Spicy',
            'geographicalCuisine' => json_encode([2, 1]),
            'ingredients' => json_encode([1, 2, 3]),
            'allergies' => json_encode([2, 3]),
            'heating_instruction_id' => '2',
            'heating_instruction_description' => 'No heating required.',
            'package' => 'Composer',
            'size' => '6363',
            'expiresIn' => "0.632620",
            'serving_unit' => 'Pieces',
            'serving_person' => 'Serving 2-3',
            'price' => '545',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('food_items')->insert([
            'chef_id' => 1,
            'dish_name' => 'pizza',
            'description' => 'ye samose ka description hai',
            'dishImage' => 'http://127.0.0.1:8000/storage/foodItem/pizza.jpeg',
            'dishImageThumbnail' => 'http://127.0.0.1:8000/storage/foodItem/thumbnail/pizza.jpeg',
            'regularDishAvailabilty' => 'No Limits',
            "foodAvailibiltyOnWeekdays" => json_encode(['Su', 'M']),
            'orderLimit' => "10",
            'foodTypeId' => '1',
            'spicyLevel' => 'Mild Spicy',
            'geographicalCuisine' => json_encode([2, 1]),
            'ingredients' => json_encode([1, 2, 3]),
            'allergies' => json_encode([2, 3]),
            'heating_instruction_id' => '2',
            'heating_instruction_description' => 'No heating required.',
            'package' => 'Composer',
            'size' => '6363',
            'expiresIn' => "0.632620",
            'serving_unit' => 'Pieces',
            'serving_person' => 'Serving 2-3',
            'price' => '545',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('food_items')->insert([
            'chef_id' => 2,
            'dish_name' => 'pizza',
            'description' => 'ye pizza ka description hai',
            'dishImage' => 'http://127.0.0.1:8000/storage/foodItem/pizza.jpeg',
            'dishImageThumbnail' => 'http://127.0.0.1:8000/storage/foodItem/thumbnail/pizza.jpeg',
            'regularDishAvailabilty' => 'No Limits',
            "foodAvailibiltyOnWeekdays" => json_encode(['Su', 'M']),
            'orderLimit' => "10",
            'foodTypeId' => '1',
            'spicyLevel' => 'Mild Spicy',
            'geographicalCuisine' => json_encode([2, 1]),
            'ingredients' => json_encode([1, 2, 3]),
            'allergies' => json_encode([2, 3]),
            'heating_instruction_id' => '2',
            'heating_instruction_description' => 'No heating required.',
            'package' => 'Composer',
            'size' => '6363',
            'expiresIn' => "0.632620",
            'serving_unit' => 'Pieces',
            'serving_person' => 'Serving 2-3',
            'price' => '545',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}