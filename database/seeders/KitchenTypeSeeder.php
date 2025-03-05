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
        DB::table('kitchentypes')->insert(['kitchentype' => 'Indian', 'image' => (env('filePath') . 'storage/admin/kitchentype/indian.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Caribbean', 'image' => (env('filePath') . 'storage/admin/kitchentype/caribbean.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'American', 'image' => (env('filePath') . 'storage/admin/kitchentype/american.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Pakistani', 'image' => (env('filePath') . 'storage/admin/kitchentype/pakistani.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Latin', 'image' => (env('filePath') . 'storage/admin/kitchentype/latinamerican.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Asian', 'image' => (env('filePath') . 'storage/admin/kitchentype/southeastasian.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Southern', 'image' => (env('filePath') . 'storage/admin/kitchentype/southern.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Mediterranean', 'image' => (env('filePath') . 'storage/admin/kitchentype/mediterranean.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Chinese', 'image' => (env('filePath') . 'storage/admin/kitchentype/chinese.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Italian', 'image' => (env('filePath') . 'storage/admin/kitchentype/italian.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'African', 'image' => (env('filePath') . 'storage/admin/kitchentype/african.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Japanese', 'image' => (env('filePath') . 'storage/admin/kitchentype/japanese.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Middle Eastern', 'image' => (env('filePath') . 'storage/admin/kitchentype/middleeastern.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Korean', 'image' => (env('filePath') . 'storage/admin/kitchentype/korean.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
        DB::table('kitchentypes')->insert(['kitchentype' => 'Mexican', 'image' => (env('filePath') . 'storage/admin/kitchentype/mexican.png'), 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}
