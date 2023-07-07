<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumetItemListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('document_item_lists')->insert(["state_id" => 1, "document_item_name" => 'food safety certificate', 'chef_type' => 'Individual', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}