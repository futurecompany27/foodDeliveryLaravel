<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('drivers')->insert([
            "firstName" => 'test',
            "lastName" => "test",
            "email" => "test",
            "mobileNo" => "9876543210",
            "are_you_a" => "Outsider",
            "password" => Hash::make('Homeshef@123'),
            "full_address" => "Test Address",
            "province" => 'Ontario',
            "city" => 'Toronto',
            "postal_code" => "J7A4N7",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}