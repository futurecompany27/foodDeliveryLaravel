<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('adminsettings')->insert([
            'default_comm' => 15,
            'refugee_comm' => 5,
            'singlemom_comm' => 5,
            'lostjob_comm' => 10,
            'student_comm' => 10,
            'food_default_comm' => 10,
            'radius' => 3,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
