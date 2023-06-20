<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KitchenTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('kitchentypes')->insert(['kitchentype' => 'All', 'image' => '', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Indian', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/indian.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Caribbean', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/caribbean.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'American', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/american.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Pakistani', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/pakistani.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Latin American', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/latinamerican.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Southeast Asian', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/southeastasian.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Southern', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/southern.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Mediterranean', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/mediterranean.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Chinese', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/mediterranean.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Italian', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/italian.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'African', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/african.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Japanese', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/japanese.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Middle Eastern', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/middleeastern.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Korean', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/korean.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Mexican', 'image' => 'http://127.0.0.1:8000/storage/admin/kitchentype/mexican.png', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}