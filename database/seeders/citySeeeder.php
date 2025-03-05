<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class citySeeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('cities')->insert(["state_id" => 1, "name" => "Rosemere", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Saint Therese", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Laval-des-rapides", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Laval", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Saint Rose", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Blainville", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Boisbriand", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Lorraine", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Bois-des-Filion", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Terrebonne", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('cities')->insert(["state_id" => 1, "name" => "Fabreville", 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}