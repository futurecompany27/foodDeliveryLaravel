<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Mail\HomeshefPasswordResetLink;
use App\Models\Admin;
use App\Models\Allergy;
use App\Models\BankName;
use App\Models\chef;
use App\Models\Dietary;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
use App\Models\Driver;
use App\Models\DriverScheduleCall;
use App\Models\Feedback;
use App\Models\FoodCategory;
use App\Models\HeatingInstruction;
use App\Models\Ingredient;
use App\Models\ScheduleCall;
use App\Models\Sitesetting;
use App\Models\State;
use App\Models\User;
use App\Models\UserContact;
use App\Notifications\Customer\NewFeedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Image; //Intervention Image
use Illuminate\Support\Facades\File;

class commonFunctions extends Controller
{

    function get_lat_long(Request $req)
    {
        try {
            $postalCode = str_replace(" ", "", strtoupper($req->postalCode));
            $url = "https://maps.googleapis.com/maps/api/geocode/xml?address=" . $postalCode . ",canada&sensor=false&key=" . env('GOOGLE_MAP_KEY');
            $result = Http::get($url);
            $xml = simplexml_load_string($result->body());
            if ($xml->status == 'OK') {
                $latitude = (float) $xml->result->geometry->location->lat;
                $longitude = (float) $xml->result->geometry->location->lng;
                $data = [
                    'result' => 1,
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
            $query = Feedback::orderBy('created_at', 'desc');
            if ($req->status == 1) {
                $query->where('status', $req->status);
            }
            $data = $query->skip($skip)->take(10)->get();
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

    function sendPasswordResetLink(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_type" => 'required',
            "email" => 'required',
        ], [
            "user_type.required" => "please fill user_type",
            "email.required" => "please fill email",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $token = Str::random(40); // Generates a random token with 40 characters
            $userDetail = [];
            if ($req->user_type == 'User') {

                User::where('email', $req->email)->update(['resetToken' => $token]);
                $data = User::where('email', $req->email)->first();
                $userDetail['full_name'] = $data->fullname;
            } else if ($req->user_type == 'Admin') {

                Admin::where('email', $req->email)->update(['resetToken' => $token]);
                $data = Admin::where('email', $req->email)->first();
                $userDetail['full_name'] = $data->name;
            } else if ($req->user_type == 'chef') {

                chef::where('email', $req->email)->update(['resetToken' => $token]);
                $data = chef::where('email', $req->email)->first();
                $userDetail['full_name'] = (ucfirst($data->first_name) . ' ' . ucfirst($data->last_name));
            } else if ($req->user_type == 'Driver') {
                $driver = Driver::where('email', $req->email)->first();
                if (!$driver) {
                    return response()->json(['message' => 'Driver not found', 'success' => false], 400);
                }
                Driver::where('email', $req->email)->update(['resetToken' => $token]);
                $data = Driver::where('email', $req->email)->first();
                $userDetail['full_name'] = (ucfirst($data->first_name) . ' ' . ucfirst($data->last_name));
            }
            $userDetail['id'] = $data->id;
            $userDetail['user_type'] = $req->user_type;
            $userDetail['token'] = $token;
            Mail::to(trim($req->email))->send(new HomeshefPasswordResetLink($userDetail));
            return response()->json(['message' => 'Password reset link has been send on mail', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function verifyToken(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_type" => 'required',
            "id" => 'required',
        ], [
            "user_type.required" => "please fill user_type",
            "id.required" => "please fill user_id",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->user_type == 'User') {
                $data = User::where(['id' => $req->id, 'resetToken' => $req->token])->first();
            } else if ($req->user_type == 'Admin') {
                $data = Admin::where(['id' => $req->id, 'resetToken' => $req->token])->first();
            } else if ($req->user_type == 'chef') {
                $data = chef::where(['id' => $req->id, 'resetToken' => $req->token])->first();
            } else if ($req->user_type == 'Driver') {
                $data = Driver::where(['id' => $req->id, 'resetToken' => $req->token])->first();
            }

            if ($data) {
                return response()->json(['message' => 'token is valid', 'success' => true], 200);
            } else {
                return response()->json(['message' => 'token is expired', 'success' => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function changePasswordwithToken(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_type" => 'required',
            "id" => 'required',
            "token" => 'required',
            "password" => 'required',
        ], [
            "user_type.required" => "please fill user_type",
            "id.required" => "please fill user_id",
            "token.required" => "please fill user_type",
            "password.required" => "please fill user_type",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->user_type == 'User') {
                $data = User::where(['id' => $req->id, 'resetToken' => $req->token])->first();
                if ($data) {
                    User::where('id', $req->id)->update(['password' => Hash::make($req->password), 'resetToken' => '']);
                }
            } else if ($req->user_type == 'Admin') {
                $data = Admin::where(['id' => $req->id, 'resetToken' => $req->token])->first();
                if ($data) {
                    Admin::where('id', $req->id)->update(['password' => Hash::make($req->password), 'resetToken' => '']);
                }
            } else if ($req->user_type == 'chef') {
                $data = chef::where(['id' => $req->id, 'resetToken' => $req->token])->first();
                if ($data) {
                    chef::where('id', $req->id)->update(['password' => Hash::make($req->password), 'resetToken' => '']);
                }
            } else if ($req->user_type == 'Driver') {
                $data = Driver::where(['id' => $req->id, 'resetToken' => $req->token])->first();
                if ($data) {
                    Driver::where('id', $req->id)->update(['password' => Hash::make($req->password), 'resetToken' => '']);
                }
            }
            return response()->json(['message' => 'Password has been changed', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }



    public function user_access_token()
    {

        // Check for the request shef registration
        // $clientIP = request()->ip();
        // ravindra IP
        $clientIP = "103.135.62.155";
        // dd($clientIP);
        //sarita  
        // $clientIP = "84.196.106.221";
        //zafeer $clientIP = "117.99.251.122";
        //himanta
        //$clientIP="49.36.114.16";
        // $clientIP="103.205.130.21"; //megabyte


        //sarita
        // $clientIP = "84.199.114.130";
        //zafeer
        //$clientIP = "117.99.251.122";

        $url = "https://ipinfo.io/" . $clientIP . "?token=24bb9a83efc13c";

        $details = json_decode(file_get_contents($url));
        //dd($details);

        /************* Or we could block all the other types of private / anonymous connections...-------**************************/
        //     if($details->vpn || $details->proxy || $details->tor || $details->hosting) {
        //         $user_access_token = 0;
        //     }
        //    else
        //    {
        //             // write your below code
        //    }

        /*****************---------------End ------------****************/

        if (isset($details->region)) {
            $province_list = State::select('name')->where('status', 1)->get();
            $province_name_array = [];
            /**
             * Collecting the province where we offer the services
             */
            for ($i = 0; $i < count($province_list); $i++) {
                array_push($province_name_array, strtolower($province_list[$i]['name']));
            }
            //dd($province_name_array);
            /**
             * Checking the region we get from Cient Device IP is belongs to the official region
             */
            //location come like   "loc": "42.1015,-72.5898",
            $location = $details->loc;
            $location_array = Explode(",", $location);


            if (in_array(strtolower($details->region), $province_name_array)) {


                $data = [
                    'user_access_token' => 1,
                    'latitude' => $location_array[0],
                    'longitude' => $location_array[1]
                ];

            } else {
                $data = [
                    'user_access_token' => 0,
                    'latitude' => "",
                    'longitude' => ""
                ];
            }

        } else {
            $data = [
                'user_access_token' => 0,
                'latitude' => "",
                'longitude' => ""
            ];
        }
        Session::put('user_access_detail', $data);
        //store it into the session for further use



    }

    // implementation of VPN  

    //  (new WebMainController)->user_access_token(); //array
    //     $user_access_detail = Session::get('user_access_detail');

    //     if ($user_access_detail['user_access_token'] == 1) {

    //         /** Shef type data */

    //         // $shef_types = ShefType::where('status', 1)->orderBy('name')->get();
    //         return  view("frontend.website.chef.become_a_shef", compact('tr', 'site_setting'));
    //         // return  view("frontend.website.chef.check_pincode_old", compact('tr', 'site_setting', 'shef_types'));
    //     } else {
    //         $msg = "We are currently not providing our service in this region.";
    //         return view('frontend.website.access_block', compact('tr', 'msg'));
    //     }
}
