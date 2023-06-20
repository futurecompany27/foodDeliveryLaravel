<?php

namespace Database\Seeders;

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
        DB::table('bank_names')->insert(['name' => 'Royal Bank of Canada', 'short_name' => 'RBC']);
        DB::table('bank_names')->insert(['name' => 'Toronto-Dominion Bank', 'short_name' => 'TD']);
        DB::table('bank_names')->insert(['name' => 'Bank of Nova Scotia', 'short_name' => 'Scotiabank']);
        DB::table('bank_names')->insert(['name' => 'Bank of Montreal', 'short_name' => 'BMO']);
        DB::table('bank_names')->insert(['name' => 'Canadian Imperial Bank of Commerce', 'short_name' => 'CIBC']);
    }
}