<?php

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SitesettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('sitesettings')->insert([
            "phone_one" => "0467869232",
            "phone_two" => '1234567895',
            "email" => 'xyzcompany@gmail.com',
            "company_name" => 'Homeplate',
            "company_address" => '12 new straat',
            "copyright" => '@Homeplate',
            "facebook" => 'https://facebook.com',
            "facebookIcon" => env('filePath') . 'storage/admin/logos/facebook.png',
            "instagram" => 'https://instagram.com',
            "instagramIcon" => env('filePath') . 'storage/admin/logos/instagram.png',
            "twitter" => 'https://twitter.com',
            "twitterIcon" => env('filePath') . 'storage/admin/logos/twitter.png',
            "youtube" => 'https://youtube.com',
            "youtubeIcon" => env('filePath') . 'storage/admin/logos/youtube.png',
            "created_by_company_link" => 'webcrafts.in',
            "created_by_company" => 'WebCrafts',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
