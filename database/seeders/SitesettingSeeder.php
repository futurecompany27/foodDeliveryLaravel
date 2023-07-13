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
            "company_name" => 'Homeshef',
            "company_address" => '12 new straat',
            "copyright" => '@Homeshef',
            "facebook" => 'https://facebook.com',
            "facebookIcon" => 'fab fa-facebook',
            "instagram" => 'https://instagram.com',
            "instagramIcon" => 'fab fa-instagram',
            "twitter" => 'https://twitter.com',
            "twitterIcon" => 'fab fa-twitter',
            "youtube" => 'https://youtube.com',
            "youtubeIcon" => 'fab fa-youtube',
            "created_by_company_link" => 'webcrafts.in',
            "created_by_company" => 'WebCrafts',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}