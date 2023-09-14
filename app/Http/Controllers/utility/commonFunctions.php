<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Allergy;
use App\Models\BankName;
use App\Models\chef;
use App\Models\Dietary;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
use App\Models\DriverScheduleCall;
use App\Models\Feedback;
use App\Models\FoodCategory;
use App\Models\HeatingInstruction;
use App\Models\Ingredient;
use App\Models\ScheduleCall;
use App\Models\Sitesetting;
use App\Models\State;
use App\Models\UserContact;
use App\Notifications\Customer\NewFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Image; //Intervention Image
use Illuminate\Support\Facades\File;

class commonFunctions extends Controller
{

    function get_lat_long(Request $req)
    {
        try {
            $postalCode = str_replace(" ", "", $req->postalCode);
            $url = "https://maps.googleapis.com/maps/api/geocode/xml?address=" . $postalCode . ",canada&sensor=false&key=" . env('GOOGLE_MAP_KEY');
            $result = Http::get($url);
            $xml = simplexml_load_string($result->body());
            if ($xml->status == 'OK') {
                $latitude = (float) $xml->result->geometry->location->lat;
                $longitude = (float) $xml->result->geometry->location->lng;
                $data = [
                    'lat' => $latitude,
                    'long' => $longitude
                ];
                return response()->json(['data' => $data, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Please check the Postal Code',], 400);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
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
                if (count($documentList) > 0) {
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
            if ($req->admin) {
                return response()->json(["data" => HeatingInstruction::all(), "success" => true], 200);
            } else {
                return response()->json(["data" => HeatingInstruction::where('status', 1)->get(), "success" => true], 200);
            }
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
            $data = Sitesetting::first();
            return response()->json(['data' => $data, 'success' => true], 200);
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
                "images" => 'required',
                "are_you_a" => 'required',
                "name" => 'required',
                "email" => 'required',
                "profession" => 'required',
                "message" => "required",
                "star_rating" => "required|integer|min:1|max:5",
            ],
            [
                "images.required" => "please fill images",
                "are_you_a.required" => "please fill Are you a?",
                "name.required" => "please fill name",
                "email.required" => "please select email",
                "profession.required" => "please select profession",
                "message.required" => "please fill message",
                "star_rating" => "please fill star_rating",
            ]
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if (!File::exists("storage/feedback_profiles/")) {
            File::makeDirectory("storage/feedback_profiles/", $mode = 0777, true, true);
        }

        $imagePath = $req->file('images')->store("feedback_profiles", "public");

        try {
            $newFeedback = new Feedback();
            $newFeedback->images = asset('storage/' . $imagePath);
            $newFeedback->are_you_a = $req->are_you_a;
            $newFeedback->name = $req->name;
            $newFeedback->email = $req->email;
            $newFeedback->profession = $req->profession;
            $newFeedback->message = $req->message;
            $newFeedback->star_rating = $req->star_rating;
            $newFeedback->save();

            $feedback = Feedback::orderBy('created_at', 'desc')->where(['are_you_a' => $req->are_you_a, 'email' => $req->email, 'profession' => $req->profession])->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new NewFeedback($feedback));
            }

            return response()->json(['message' => "Feedback submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getSiteFeedback(Request $req)
    {
        try {
            $totalRecords = Feedback::count();
            $skip = $req->page * 10;
            $data = Feedback::orderBy('created_at', 'desc')->skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateSiteFeedbackStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Feedback::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getAllScheduleCall(Request $req)
    {
        try {
            $totalRecords = ScheduleCall::count();
            $skip = $req->page * 10;
            $data = ScheduleCall::with('chef')->skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateScheduleCallStatus(Request $req)
    {

        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            ScheduleCall::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getAllDriverScheduleCall(Request $req)
    {
        try {
            $totalRecords = DriverScheduleCall::count();
            $skip = $req->page * 10;
            $data = DriverScheduleCall::with('driver')->skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateDriverScheduleCallStatus(Request $req)
    {

        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DriverScheduleCall::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }


    public function getAllChefs(Request $req)
    {
        try {
            $totalRecords = chef::count();
            $skip = $req->page * 10;
            $data = chef::skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
}
