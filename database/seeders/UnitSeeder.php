<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            [
                'name' => 'Packet',
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
            [
                'name' => 'Dozen',
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
            [
                'name' => 'Box',
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
            [
                'name' => 'KG',
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
            [
                'name' => 'Ounce',
                'created_at' => Carbon::now()->toDateTimeString(),
            ],
        ];
        $units_array = [];
        foreach ($units as $unit) {
            array_push($units_array, $unit);
        }
        DB::table('size_units')->insert($units_array);
    }
}