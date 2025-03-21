<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ShefSubTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('shef_subtypes')->insert(['type_id' => 1, 'name' => 'Home cooker', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}