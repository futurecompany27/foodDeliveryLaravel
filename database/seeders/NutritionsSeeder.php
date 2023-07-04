<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class NutritionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('nutritions')->insert(['nutrition_name' => 'Contains gluten', 'small_description' => 'Has gluten, wheat or grain proteins', 'image' => 'http://127.0.0.1:8000/storage/admin/allergen_icons/containsgluten.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('nutritions')->insert(['nutrition_name' => 'Contains dairy', 'small_description' => 'Has milk, Ghee,etc', 'image' => 'http://127.0.0.1:8000/storage/admin/allergen_icons/containsdairy.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('nutritions')->insert(['nutrition_name' => 'Contains nuts', 'small_description' => 'Has nuts', 'image' => 'http://127.0.0.1:8000/storage/admin/allergen_icons/containsnuts.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}