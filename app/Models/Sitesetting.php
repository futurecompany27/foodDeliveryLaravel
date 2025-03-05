<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sitesetting extends Model
{
    use HasFactory;
    protected $table = "sitesettings";
    protected $fillable = ['id', 'phone_one', 'phone_two', 'email', 'company_name', 'company_address', 'copyright', 'facebook', 'facebookIcon', 'instagram', 'instagramIcon', 'twitter', 'twitterIcon', 'youtube', 'youtubeIcon','created_by_company_link','created_by_company'];
}
