<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StatesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('states')->insert(['country_id' => 1, 'name' => 'Quebec', 'tax_type' => json_encode(["GST", "PST"]), 'tax_value' => json_encode([25, 50]), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}
