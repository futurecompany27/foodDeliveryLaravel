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
        DB::table('cities')->insert([
            "state_id" => 1,
            "name" => "Toronto",
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
