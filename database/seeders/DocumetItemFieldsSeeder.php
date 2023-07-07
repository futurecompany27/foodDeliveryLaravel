<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DocumetItemFieldsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('document_item_fields')->insert(['document_item_list_id' => 1, 'field_name' => 'Licence number', 'type' => 'text', 'created_at' => Carbon::now(), 'updated_at' => Carbon::now()]);
    }
}