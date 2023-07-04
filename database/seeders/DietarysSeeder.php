<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DietarysSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('dietaries')->insert(['diet_name' => 'vegan', 'diet_image' => 'http://127.0.0.1:8000/storage/admin/dietaries_icons/vegan.png', 'small_description' => 'No animal products like eggs, butter, ghee, honey, or milk.', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}