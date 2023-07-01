<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Models\BankName;
use App\Models\chef;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class commonFunctions extends Controller
{
    function get_lat_long($postal)
    {
        $postal = str_replace(" ", "", $postal);
        $url = "https://maps.googleapis.com/maps/api/geocode/xml?address=" . $postal . ",canada&sensor=false&key=AIzaSyAbW2JsS5yI_X2Mmh8LBcF6ItH2aHqgzfc";

        $result = Http::get($url);
        $xml = simplexml_load_string($result->body());
        if ($xml->status == 'OK') {
            Log::info($xml);
            $latitude = (float) $xml->result->geometry->location->lat;
            $longitude = (float) $xml->result->geometry->location->lng;

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

    function getAllBankList(Request $req)
    {
        $data = BankName::all();
        return response()->json(["data" => $data, "success" => true], 200);
    }

    function getDocumentListAccToChefTypeAndState(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            $allFeilds = [];
            $chefDetail = chef::find($req->chef_id);
            $stateDetail = State::where('name', $chefDetail->state)->first();
            if ($stateDetail) {
                $documentList = DocumentItemList::where(["state_id" => $stateDetail->id])->get();
                Log::info($documentList);
                if (count($documentList) > 0) {
                    Log::info("///////////////");
                    foreach ($documentList as $value) {
                        $docFeilds = DocumentItemField::where('document_item_list_id', $value->id)->get();
                        foreach ($docFeilds as $val) {
                            array_push($allFeilds, $val) ;
                        }
                    }
                }
            }
            return response()->json(['data' => $allFeilds, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !' . $th->getMessage(), 'success' => false], 500);
        }
    }
}