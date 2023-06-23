<?php

namespace App\Http\Controllers\chefs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Http\Controllers\utility\commonFunctions;
use App\Mail\HomeshefChefEmailVerification;
use App\Models\chef;
use App\Models\ChefDocument;
use App\Models\City;
use App\Models\State;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Pincode;
use Illuminate\Support\Facades\Validator;
use Image; //Intervention Image
use File;

class ChefController extends Controller
{
    function ChefRegisteration(Request $req)
    {
        try {
            DB::beginTransaction();
            // $checkPinCode = Pincode::where('pincode', str_replace(" ", "", strtolower($req->pincode)))->first();
            // if (!$checkPinCode) {
            //     return response()->json(["msg" => 'we are not offering our services in this region', 'success' => false], 400);
            // }

            $chefExist = chef::where("email", $req->email)->first();
            if ($chefExist) {
                return response()->json(['error' => "This email is already register please use another email!", "success" => false], 400);
            }
            $chefExist = chef::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefExist) {
                return response()->json(['error' => "This mobileno is already register please use another mobileno!", "success" => false], 400);
            }
            $chef = new chef();
            $chef->first_name = ucfirst($req->first_name);
            $chef->last_name = ucfirst($req->last_name);
            $chef->date_of_birth = $req->date_of_birth;
            $chef->postal_code = str_replace(" ", "", (strtolower($req->postal_code)));
            $chef->mobile = str_replace("-", "", $req->mobile);
            $chef->is_mobile_verified = 0;
            $chef->email = $req->email;
            $chef->password = Hash::make($req->password);
            if ($req->newToCanada == 1) {
                $chef->new_to_canada = $req->newToCanada;
            }

            if ($req->postal_code) {
                $pinCodeDetail = Pincode::where('pincode', str_replace(" ", "", (strtolower($req->postal_code))))->first();
                $chef->latitude = $pinCodeDetail->latitude;
                $chef->longitude = $pinCodeDetail->longitude;
                $cityDetail = City::where('id', $pinCodeDetail->city_id)->first();
                $stateDetails = State::where('id', $cityDetail->state_id)->first();
                $chef->state = $stateDetails->name;
            }

            $chef->save();
            $chefDetail = chef::find($chef->id);

            $userExist = User::where("email", $req->email)->first();
            if (!$userExist) {
                // creating new instance of user Controller so that we can access function of userController 
                $UserController = new UserController;
                $request = new Request();
                $request->merge([
                    "fullname" => ucfirst($req->first_name) . " " . ucfirst($req->last_name),
                    "mobile" => $req->mobile,
                    "email" => $req->email,
                    "password" => $req->password
                ]);
                $UserController->UserRegisteration($request);
            }

            Mail::to(trim($req->email))->send(new HomeshefChefEmailVerification($chefDetail));

            DB::commit();
            return response()->json(['message' => 'Register successfully!', "data" => $chefDetail, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function EditPersonalInfo(Request $req)
    {

        $validator = Validator::make($req->all(), [
            "profile_pic" => 'nullable',
            "chef_id" => 'required',
            "first_name" => 'required',
            "last_name" => 'required',
            "type" => 'required',
            "sub_type" => "required",
            "address_line1" => "required",
            "postal_code" => 'required'
        ], [
            "chef_id.required" => "please mention chef_id",
            "first_name.required" => "please fill firstname",
            "last_name.required" => "please fill lastname",
            "type.required" => "please select type",
            "sub_type.required" => "please select sub-type",
            "address_line1.required" => "please fill addressLine1",
            "postal_code" => "please fill postal code"
        ]);

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }

        try {
            if ($req->hasFile('profile_pic')) {
                $chefDetail = chef::find($req->chef_id);
                $path = str_replace(url('storage'), 'public', $chefDetail->profile_pic);

                if (isset($chefDetail->profile_pic) && File::exists($path)) {
                    unlink($path);
                }
                $name_gen = hexdec(uniqid()) . '.' . $req->file('profile_pic')->getClientOriginalExtension();
                if (!File::exists("storage/chef/")) {
                    File::makeDirectory("storage/chef/", $mode = 0777, true, true);
                }
                $small_image = Image::make($req->file('profile_pic'))
                    ->resize(100, 100)
                    ->save("storage/chef/" . $name_gen);
                $profile = asset('storage/chef/' . $name_gen);
            }

            $result = chef::where('id', $req->chef_id)->update([
                "first_name" => ucfirst($req->first_name),
                "last_name" => ucfirst($req->last_name),
                "type" => ucfirst($req->type),
                "sub_type" => ucfirst($req->sub_type),
                "address_line1" => htmlspecialchars(ucfirst($req->address_line1)),
                "postal_code" => strtoupper($req->postal_code),
                "profile_pic" => isset($profile) ? $profile : ''
            ]);
            return response()->json(["msg" => "profile updated successfully", "success" => true], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function ChefLogin(Request $req)
    {
        $rules = [
            'email' => 'required | email',
            'password' => 'required'
        ];
        $validate = Validator::make($req->all(), $rules);
        if ($validate->fails()) {
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        $chefDetail = chef::where("email", $req->email)->first();

        if ($chefDetail) {
            $chefDetail->makeVisible('password');
            if (Hash::check($req->password, $chefDetail['password'])) {
                $chefDetail->makeHidden('password');
                return response()->json(['message' => 'Logged in successfully!', 'data' => $chefDetail, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = chef::find($req->chef_id);
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateChefPrimaryEmail(Request $req)
    {
        if (!$req->chef_id || !$req->new_email) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $chefDetails = chef::find($req->chef_id);
            if ($chefDetails->email == $req->new_email) {
                return response()->json(['error' => 'Trying to use an existing primary email. Please use another email.', "success" => false], 500);
            }

            $isNewEmailAlreadyRegistered = chef::where('email', $req->new_email)->first();
            if (!$isNewEmailAlreadyRegistered) {
                $chefDetails->email = trim($req->new_email);
                $chefDetails->is_email_verified = 0;
                $chefDetails->email_verified_at = null;
                $chefDetails->save();
                Mail::to(trim($req->new_email))->send(new HomeshefChefEmailVerification($chefDetails));
                return response()->json(['msg' => "Updated sucessfully", "success" => true], 200);
            } else {
                return response()->json(["error" => "This email is already registerd with Homeshef", "success" => false], 500);
            }

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateSocialMediaLinks(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);
            if ($req->twitter_link) {
                $chef->twitter_link = $req->twitter_link;
            }
            if ($req->facebook_link) {
                $chef->facebook_link = $req->facebook_link;
            }
            if ($req->tiktok_link) {
                $chef->tiktok_link = $req->tiktok_link;
            }
            $chef->save();
            return response()->json(['msg' => 'Updated successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateBankDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "bank_name" => 'required',
            "account_number" => 'required',
            "transit_number" => 'required',
            "institution_number" => 'required',
        ], [
            "chef_id.required" => "please mention chef_id",
            "bank_name.required" => "please fill bank_name",
            "account_number.required" => "please fill account_number",
            "transit_number.required" => "please select transit_number",
            "institution_number.required" => "please select institution_number",
        ]);

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);
            $chef->bank_name = $req->bank_name;
            $chef->account_number = $req->account_number;
            $chef->transit_number = $req->transit_number;
            $chef->institution_number = $req->institution_number;
            $chef->save();
            return response()->json(['msg' => "updated successfully", "success" => true], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateDocuments(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['error' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            // Log::info($req);
            $path = 'storage/chef/' . $req->chef_id;
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }

            // store address proof 
            if (isset($req->address_proof) && $req->hasFile('address_proof_file')) {
                $name_gen = hexdec(uniqid()) . '.' . $req->file('address_proof_file')->getClientOriginalExtension();
                $req->file('address_proof_file')->storeAs($path, $name_gen);
                chef::where("id", $req->chef_id)->update(["address_proof_file" => asset($path . $name_gen), "address_proof" => $req->address_proof]);
            }

            // store ID proof 1
            if ($req->hasFile('id_proof_1')) {
                $name_gen = hexdec(uniqid()) . '.' . $req->file('id_proof_1')->getClientOriginalExtension();
                $req->file('id_proof_1')->storeAs($path, $name_gen);
                chef::where("id", $req->chef_id)->update(["id_proof_1" => asset($path . $name_gen)]);
            }

            // store ID proof 2
            if ($req->hasFile('id_proof_2')) {
                $name_gen = hexdec(uniqid()) . '.' . $req->file('id_proof_2')->getClientOriginalExtension();
                $req->file('id_proof_2')->storeAs($path, $name_gen);
                chef::where("id", $req->chef_id)->update(["id_proof_2" => asset($path . $name_gen)]);
            }

            // Additional fields which has values in string
            if (isset($req->typeTextData)) {
                $textTypeData = $req->typeTextData;
                foreach ($textTypeData as $value) {
                    $data = json_decode($value);
                    if (isset($data->value) && $data->value != "") {
                        ChefDocument::updateOrCreate(
                            [
                                "chef_id" => $req->chef_id,
                                "document_field_id" => $data->id
                            ],
                            [
                                "field_value" => $data->value
                            ]
                        );
                    }
                }
            }

            // Additional fields which has values in files
            if (isset($req->files) && isset($req->id)) {
                $fieldsArray = $req->input('id');
                $filesArray = $req->file('files');
                Log::info($fieldsArray);
                foreach ($fieldsArray as $index => $value) {
                    if (isset($filesArray[$index])) {

                        $name_gen = hexdec(uniqid()) . '.' . $filesArray[$index]->getClientOriginalExtension();
                        $filesArray[$index]->storeAs($path, $name_gen);

                        ChefDocument::updateOrCreate(
                            [
                                "chef_id" => $req->chef_id,
                                "document_field_id" => $value
                            ],
                            [
                                "field_value" => asset($path . $name_gen)
                            ]
                        );
                    }
                }
            }
            DB::commit();
            return response()->json(['msg' => "updated successfully", 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

}