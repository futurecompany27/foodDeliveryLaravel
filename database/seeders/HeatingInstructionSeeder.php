<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HeatingInstructionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('heating_instructions')->insert([
            'title' => 'Not Required',
            'description' => 'No heating required.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Microwave (For Ready Meals)',
            'description' => 'Transfer meal to a microwave-safe dish. Heat on high for 3-4 minutes. Stir and continue heating in 1-minute increments, if necessary, until thoroughly hot.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Oven (For Casseroles)',
            'description' => 'Preheat oven to 350°F (175°C). Remove casserole from packaging and cover with foil. Bake for 20-25 minutes or until heated through. Allow the casserole to rest for a few minutes before serving.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Stovetop (For Soups)',
            'description' => 'Pour soup into a saucepan. Warm on medium heat, stirring occasionally, until the soup is hot. Do not let the soup boil.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Microwave (For Pasta Dishes)',
            'description' => 'Transfer pasta to a microwave-safe dish. Add a few tablespoons of water, cover, and heat on high for 2-3 minutes. Stir and heat in 1-minute increments if necessary.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Oven (For Baked Goods)',
            'description' => 'Preheat oven to 375°F (190°C). Place baked goods on a baking sheet and heat for 10-15 minutes or until warmed through.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Stovetop (For Sauces)',
            'description' => 'Pour sauce into a saucepan. Heat gently over low heat, stirring occasionally, until hot. Be careful not to let the sauce boil unless directed otherwise.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Microwave (For Vegetables)',
            'description' => 'Place vegetables in a microwave-safe dish. Add a few tablespoons of water, cover, and heat on high for 2-3 minutes. Stir and continue heating in 1-minute increments if necessary.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Oven (For Pizza)',
            'description' => 'Preheat oven to 425°F (220°C). Place pizza directly on the center oven rack and bake for 12-15 minutes, or until the crust is golden and cheese is melted.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Stovetop (For Rice Dishes)',
            'description' => 'Transfer rice to a saucepan. Add a few tablespoons of water or broth, cover, and heat on low for 8-10 minutes, stirring occasionally.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Microwave (For Desserts)',
            'description' => 'Place dessert in a microwave-safe dish. Heat on medium power for 1-2 minutes or until heated through. Be careful not to overheat as some fillings can become extremely hot.',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('heating_instructions')->insert([
            'title' => 'Other',
            'description' => 'Write your own detail',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}