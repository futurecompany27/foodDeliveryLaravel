<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AllergiesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('allergies')->insert(['allergy_name' => 'Contains gluten', 'small_description' => 'Has gluten, wheat or grain proteins', 'image' => (env('filePath').'storage/admin/allergen_icons/gluten.svg'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('allergies')->insert(['allergy_name' => 'Contains dairy', 'small_description' => 'Has milk, Ghee,etc', 'image' => (env('filePath').'storage/admin/allergen_icons/dairy.svg'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('allergies')->insert(['allergy_name' => 'Contains nuts', 'small_description' => 'Has nuts', 'image' => (env('filePath') . 'storage/admin/allergen_icons/nuts.svg'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}