<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class otpController extends Controller
{
    function sendOTP(Request $req)
    {
        if (!$req->mobile) {
            return response()->json(['error' => "please fill required fields", "success" => false], 400);
        }
        try {
            $otp = Otp::updateOrCreate(
                ['mobile' => str_replace("-", "", $req->mobile)],
                ['otp_number' => 1111]
            );
            return response()->json(['msg' => "Otp has been sent successfully", "otp" => 1111, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
    
    function verifyOtp(Request $req)
    {
        if (!$req->mobile || !$req->otp) {
            return response()->json(['error' => "please fill required fields", "success" => false], 400);
        }
        try {
            $verified = Otp::where(["mobile" => $req->mobile, "otp_number" => $req->otp])->first();
            if ($verified) {
                return response()->json(['msg' => "verified successfully", "success" => true], 200);
            } else {
                return response()->json(['msg' => "Invalid Otp", "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th);
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

}