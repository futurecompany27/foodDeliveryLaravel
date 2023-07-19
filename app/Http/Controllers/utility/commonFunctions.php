<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Models\Allergy;
use App\Models\BankName;
use App\Models\chef;
use App\Models\Dietary;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
use App\Models\Feedback;
use App\Models\FoodCategory;
use App\Models\HeatingInstruction;
use App\Models\Ingredient;
use App\Models\Sitesetting;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Image; //Intervention Image
use Illuminate\Support\Facades\File;

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
        try {
            return response()->json(['data' => BankName::all(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getDocumentListAccToChefTypeAndState(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all the fields', 'success' => false], 400);
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
                            array_push($allFeilds, $val);
                        }
                    }
                }
            }
            return response()->json(['data' => $allFeilds, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllFoodTypes()
    {
        try {
            return response()->json(['data' => FoodCategory::all(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllHeatingInstructions(Request $req)
    {
        try {
            return response()->json(["data" => HeatingInstruction::all(), "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllAllergens(Request $req)
    {
        try {
            return response()->json(['data' => Allergy::all(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllDietaries(Request $req)
    {
        try {
            return response()->json(['data' => Dietary::all(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllIngredients(Request $req)
    {
        try {
            return response()->json(['data' => Ingredient::all(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }

    function getAllSiteSettings(Request $req)
    {
        try {
            $data = Sitesetting::all();
            return response()->json(['data' => $data[0], 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }
    function giveSiteFeedback(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                "are_you_a" => 'required',
                "name" => 'required',
                "email" => 'required',
                "profession" => 'required',
                "message" => "required",
                "star_rating" => "required|integer|min:1|max:5",
            ],
            [
                "are_you_a.required" => "please fill Are you a?",
                "name.required" => "please fill name",
                "email.required" => "please select email",
                "profession.required" => "please select profession",
                "message.required" => "please fill message",
                "star_rating" => "please fill star_rating",
            ]
        );

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        if (!File::exists("storage/feedback_profiles/")) {
            File::makeDirectory("storage/feedback_profiles/", $mode = 0777, true, true);
        }
        try {
            $feedback = new Feedback();
            if ($req->hasFile('images')) {
                $imagePath = $req->file('images')->store("feedback_profiles", "public");
                $feedback->images = asset('storage/' . $imagePath);
            }
            $feedback->are_you_a = $req->are_you_a;
            $feedback->name = $req->name;
            $feedback->email = $req->email;
            $feedback->profession = $req->profession;
            $feedback->message = $req->message;
            $feedback->star_rating = $req->star_rating;
            $feedback->save();
            return response()->json(['msg' => "Feedback submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
    function getSiteFeedback()
    {
        $data = Feedback::all();
        foreach ($data as $value) {
            $value->images = json_decode($value->images);
        }
        return response()->json(['data' => $data, "success" => true], 200);
    }

}