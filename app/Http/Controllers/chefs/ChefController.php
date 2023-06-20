<?php

namespace App\Http\Controllers\chefs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Http\Controllers\utility\commonFunctions;
use App\Mail\HomeshefChefEmailVerification;
use App\Models\chef;
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
            // $commonFunctions = new commonFunctions;
            // $lat_long = $commonFunctions->get_lat_long(str_replace(" ", "", (strtolower($req->postal_code))));
            // log::info($lat_long);
            // $chef->latitude = $lat_long['lat'];
            // $chef->longitude = $lat_long['long'];

            $chef->latitude = 45.618200;
            $chef->longitude = -73.797240;
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
            return response()->json(['message' => 'Register successfully!', "data" => $chefDetail, 'success' => true]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
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

    function EditChefDocuments(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "address_proof" => 'required',
            "address_proof_file" => 'required',
            "id_proof1_file" => 'required',
            "id_proof2_file" => 'required',
        ], [
            "chef_id.required" => "please mention chef_id",
            "address_proof.required" => "please select address proof type",
            "address_proof_file.required" => "please select address proof type",
            "id_proof1_file.required" => "please upload id prood 1 ",
            "id_proof2_file.required" => "please upload id prood 2",
        ]);

        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {

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

}