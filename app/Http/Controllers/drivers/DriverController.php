<?php

namespace App\Http\Controllers\drivers;

use App\Http\Controllers\Controller;
use App\Mail\HomeshefDriverEmailVerificationLink;
use App\Models\Admin;
use App\Models\Driver;
use App\Notifications\Driver\driverRegisterationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;

class DriverController extends Controller
{
    function driverRegisteraion(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "first_name" => 'required',
            "last_name" => 'required',
            "email" => 'required',
            "mobileNo" => 'required',
            "are_you_a" => 'required',
            "password" => 'required',
            "full_address" => 'required',
            "province" => 'required',
            "city" => 'required',
            "postal_code" => 'required',
        ], [
            "first_name.required" => "please fill email",
            "last_name.required" => "please fill email",
            "email.required" => "please fill email",
            "mobileNo.required" => "please fill mobileNo",
            "are_you_a.required" => "please fill select driver",
            "password.required" => "please fill password",
            "full_address" => 'please fill full_address',
            "province" => 'please fill province',
            "city" => 'please fill city',
            "postal_code" => 'please fill postal_code',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driverExist = Driver::where('email    ', $req->email)->first();
            if ($driverExist) {
                return response()->json(["message" => 'Email already registered', "success" => false], 500);
            } else {
                $driver = new Driver();
                $driver->first_name = $req->first_name;
                $driver->last_name = $req->last_name;
                $driver->email = $req->email;
                $driver->mobileNo = $req->mobileNo;
                $driver->are_you_a = $req->are_you_a;
                $driver->password = Hash::make($req->password);
                $driver->full_address = $req->full_address;
                $driver->province = $req->province;
                $driver->city = $req->city;
                $driver->postal_code = $req->postal_code;
                $driver->save();
                Mail::to(trim($req->email))->send(new HomeshefDriverEmailVerificationLink($driver));
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new driverRegisterationNotification($driver));
                }
                return response()->json(["message" => 'Registered successfully', "success" => true, "driver_id" => $driver->id], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function driverLogin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "userName" => 'required',
            "password" => 'required',
        ], [
            "userName.required" => "please fill User Name",
            "password.required" => "please fill password",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = Driver::where('email', $req->userName)->first();
            if (!$driver) {
                $driver = Driver::where('mobileNo', $req->userName)->first();
            }
            $driver->makeVisible('password');
            if ($driver && Hash::check($req->password, $driver->password)) {
                $driver->makeHidden('password');
                return response()->json(['message' => 'Logged in successfully!', 'data' => $driver, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function driverForgetPassword(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "userName" => 'required',
            "password" => 'required',
        ], [
            "password.required" => "please fill password",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = Driver::where('email', $req->userName)->first();
            if (!$driver) {
                $driver = Driver::where('mobileNo', $req->userName)->first();
            }
            Driver::where('email', $driver->email)->update(['password' => Hash::make($req->password)]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function updateDrivingLicence(Request $req) {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "driving_licence_no" => 'required',
        ], [
            "id.required" => "please fill password",
            "driving_licence_no.required" => "please fill driving licence no",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = 'chef/' . $req->chef_id;
            if ($req->hasFile('driving_licence_proof')) {
                $driver = Driver::find($req->id);
                if (file_exists(str_replace(env('filePath'), '', $driver->driving_licence_proof))) {
                    unlink(str_replace(env('filePath'), '', $driver->driving_licence_proof));
                }
                $storedPath = $req->file('address_proof_path')->store($path, 'public');
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }
}