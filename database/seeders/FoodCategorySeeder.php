<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class FoodCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $path = "/admin/food_category";
        if (!File::exists($path)) {
            File::makeDirectory($path, $mode = 0777, true, true);
        }
        DB::table('food_categories')->insert(['category' => 'Appetizer & Starters', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/cooking.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('food_categories')->insert(['category' => 'Beverage', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/french-fries.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('food_categories')->insert(['category' => 'Bundle', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/steamed-fish.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('food_categories')->insert(['category' => 'Kids', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/meal.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('food_categories')->insert(['category' => 'Main Courses', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/english-breakfast.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('food_categories')->insert(['category' => 'Breads & Rolls', 'commission' => 0, 'image' => "http://127.0.0.1:8000/storage/admin/food_category/hamburger.png", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}
