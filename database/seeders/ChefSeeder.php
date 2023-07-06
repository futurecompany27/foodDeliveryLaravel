<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ChefSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('chefs')->insert([
            "first_name" => 'Ravindra',
            "last_name" => "Maurya",
            "date_of_birth" => "2001-11-04",
            "postal_code" => 'M4L',
            "mobile" => "1111111111",
            "is_mobile_verified" => "0",
            "email" => "ravindra@gmail.com",
            "password" => Hash::make('Homeshef@123'),
            "kitchen_name" => "Ravindra's Kitchen",
            "about_kitchen" => "We have 1 year of experience in cooking with all type of cuisines",
            "kitchen_types" => ['Indian', "American"],
        ]);
        DB::table('chefs')->insert([
            "first_name" => 'Ravi',
            "last_name" => "Mandal",
            "date_of_birth" => "2005-11-04",
            "postal_code" => 'M4L',
            "mobile" => "1111111111",
            "is_mobile_verified" => "0",
            "email" => "ravi@gmail.com",
            "password" => Hash::make('Homeshef@123'),
            "kitchen_name" => "Ravi's Kitchen",
            "about_kitchen" => "We have 1 year of experience in cooking with all type of cuisines",
            "kitchen_types" => ['Indian', "American"],
        ]);
        DB::table('chefs')->insert([
            "first_name" => 'Sarita',
            "last_name" => "ma'am",
            "date_of_birth" => "1980-11-04",
            "postal_code" => 'M4L',
            "mobile" => "1111111111",
            "is_mobile_verified" => "0",
            "email" => "sarita@gmail.com",
            "password" => Hash::make('Homeshef@123'),
            "kitchen_name" => "Sarita's Kitchen",
            "about_kitchen" => "We have 10 year of experience in cooking with all type of cuisines",
            "kitchen_types" => ['Indian', "American"],
        ]);
        DB::table('chefs')->insert([
            "first_name" => 'himanta',
            "last_name" => "ma'am",
            "date_of_birth" => "1990-11-04",
            "postal_code" => 'M4L',
            "mobile" => "1111111111",
            "is_mobile_verified" => "0",
            "email" => "himanta@gmail.com",
            "password" => Hash::make('Homeshef@123'),
            "kitchen_name" => "Himanta's Kitchen",
            "about_kitchen" => "We have 10 year of experience in cooking with all type of cuisines",
            "kitchen_types" => ['Indian', "American"],
        ]);
    }
}