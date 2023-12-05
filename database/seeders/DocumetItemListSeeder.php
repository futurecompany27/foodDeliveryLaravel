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
        DB::table('document_item_lists')->insert([
            "state_id" => 1,
            "document_item_name" => 'FOOD SAFETY CERTIFICATE',
            'reference_links' => 'https://www.mapaq.gouv.qc.ca/Formateurs_hygiene_salubrite/',
            'additional_links' => 'https://www.mapaq.gouv.qc.ca/Formateurs_hygiene_salubrite/',
            'chef_type' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('document_item_lists')->insert([
            "state_id" => 1,
            "document_item_name" => 'RESTAURANT AND RETAIL LICENSES',
            'reference_links' => 'https://www.mapaq.gouv.qc.ca/SiteCollectionDocuments/Formulaires/presdetstaf.pdf',
            'additional_links' => 'https://www.mapaq.gouv.qc.ca/SiteCollectionDocuments/Formulaires/presdetstaf.pdf',
            'chef_type' => 1,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
