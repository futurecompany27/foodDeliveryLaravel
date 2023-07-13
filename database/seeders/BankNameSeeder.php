<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class BankNameSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('bank_names')->insert(['name' => 'Royal Bank of Canada', 'short_name' => 'RBC', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('bank_names')->insert(['name' => 'Toronto-Dominion Bank', 'short_name' => 'TD', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('bank_names')->insert(['name' => 'Bank of Nova Scotia', 'short_name' => 'Scotiabank', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('bank_names')->insert(['name' => 'Bank of Montreal', 'short_name' => 'BMO', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('bank_names')->insert(['name' => 'Canadian Imperial Bank of Commerce', 'short_name' => 'CIBC', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}