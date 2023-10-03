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
        // DB::table('pincodes')->insert(["city_id" => 1, "pincode" => "J7A4N7", "latitude" => "45.6524509", "longitude" => "-73.7814507", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 1, "pincode" => "H7A", "latitude" => "45.678657", "longitude" => "-73.5894284", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 2, "pincode" => "J7E", "latitude" => "45.6933081", "longitude" => "-73.8226268", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 3, "pincode" => "H7N", "latitude" => "45.5590767", "longitude" => "-73.699898", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 4, "pincode" => "H7W", "latitude" => "45.5305808", "longitude" => "-73.7700213", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 5, "pincode" => "H7L", "latitude" => "45.5984541", "longitude" => "-73.7706136", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 6, "pincode" => "J7B", "latitude" => "45.6628665", "longitude" => "-73.8134978", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 7, "pincode" => "J7H", "latitude" => "45.6235008", "longitude" => "-73.8597435", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 9, "pincode" => "J6Z", "latitude" => "45.6664276", "longitude" => "-73.7712205", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 10, "pincode" => "J7M", "latitude" => "45.7810875", "longitude" => "-73.7245013", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('pincodes')->insert(["city_id" => 11, "pincode" => "H7P", "latitude" => "45.5734498", "longitude" => "-73.7985357", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}