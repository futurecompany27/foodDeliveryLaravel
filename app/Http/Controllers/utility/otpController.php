<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class otpController extends Controller
{
    function sendOTP(Request $req)
    {
        try {
            // $otp = mt_rand(1000, 9999);
            $otp = 1111;
            if ($req->mobile) {
                // $otp_msg = $otp . "+is+your+one+time+verification+code+for+HomeShef";
                // $url = "https://platform.clickatell.com/messages/http/send?apiKey=WzKPQFifSAe-c5nFp7SynQ==&to=1" . $req->mobile . "&content=" . $otp_msg;
                // $response = Http::get($url);
                Otp::updateOrCreate(
                    ['mobile' => str_replace("-", "", $req->mobile)],
                    ['otp_number' => $otp]
                );
            } elseif ($req->email) {
                Otp::updateOrCreate(['email' => $req->email], ['otp_number' => $otp]);
            }
            return response()->json(['message' => "Otp has been sent successfully", "otp" => $otp, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            ;
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function verifyOtp(Request $req)
    {
        if (!$req->otp) {
            return response()->json(['message' => "please fill required fields", "success" => false], 400);
        }
        try {
            if ($req->mobile) {
                $verified = Otp::where(["mobile" => str_replace("-", "", $req->mobile), "otp_number" => $req->otp])->first();
            }
            if ($req->email) {
                $verified = Otp::where(["email" => $req->email, "otp_number" => $req->otp])->first();
            }
            if ($verified) {
                return response()->json(['message' => "verified successfully", "success" => true], 200);
            } else {
                return response()->json(['message' => "Invalid OTP", "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            ;
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }


}