<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class postalCodeSeeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('pincodes')->insert([
            "city_id" => 1,
            "pincode" => "M4L",
            "latitude" => "95.2000014",
            "longitude" => "95.2000014",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('pincodes')->insert([
            "city_id" => 1,
            "pincode" => "J7A4N7",
            "latitude" => "45.6524509",
            "longitude" => "-73.7814507",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
