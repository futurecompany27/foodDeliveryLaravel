<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class commonFunctions extends Controller
{
    function get_lat_long($postal)
    {
        $postal = str_replace(" ", "", $postal);
        $gmk = env('GOOGLE_MAP_KEY');
        Log::info("///////////////", $gmk);
        $url = "https://maps.googleapis.com/maps/api/geocode/xml?address=" . $postal . ",canada&sensor=false&key=" . $gmk;

        // $result = simplexml_load_file($url);
        $result = Http::get($url);
        if ($result->successful()) {
            Log::info("kkkkkkkkk",[$result->body()]);
        }
        Log::info("///////////////",[$result]);
        if (isset($result->result->geometry)) {
            $latitude = json_decode($result->result->geometry->location->lat);
            $longitude = json_decode($result->result->geometry->location->lng);

            // $latitude = 45.618200;  for J7A1A4
            // $longitude =-73.797240; for J7A1A4
            $data = [
                'result' => 1,
                'lat' => $latitude,
                'long' => $longitude
            ];
        } else {
            $data = [
                'result' => 0,
                'message' => 'Please check the Postal Code'
            ];
        }
        return $data;
    }
}