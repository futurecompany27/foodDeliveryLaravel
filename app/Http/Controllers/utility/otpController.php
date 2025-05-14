<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Mail\DriverPasswordResetOtp;
use App\Mail\DriverSendOTP;
use App\Mail\SendOtpToEmail;
use App\Models\Chef;
use App\Models\Driver;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class otpController extends Controller
{

    function sendOTP(Request $req)
    {
        try {
            if ($req->mobile) {
                $otp = 1111;
                //$otp_msg = $otp . "+is+your+one+time+verification+code+for+Homeplate";
                //$url = "https://platform.clickatell.com/messages/http/send?apiKey=WzKPQFifSAe-c5nFp7SynQ==&to=1" . $req->mobile . "&content=" . $otp_msg;
                //$response = Http::get($url);
                $cleanMobile = str_replace("-", "", $req->mobile);
                // Check if the mobile number already exists in the chefs table
                $existingChef = Chef::where('mobile', $cleanMobile)->first();

                if ($existingChef) {
                    return response()->json([
                        'message' => 'Mobile number already exists',
                        'success' => false
                    ], 409); 
                }
                Otp::updateOrCreate(
                    ['mobile' => str_replace("-", "", $req->mobile)],
                    ['otp_number' => $otp]
                );
                return response()->json(['message' => ("A OTP has been sent to your " . ($req->mobile ? "mobile no. +1 " . $req->mobile : "account")), "otp" => $otp, "success" => true], 200);
            }
            if ($req->email) {
                $userType = $this->getAuthenticatedUserType();
                if (!$userType) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                // Identify the user based on the guard
                switch ($userType) {
                    case 'user':
                        $user = auth()->guard('user')->user();
                        break;
                    case 'chef':
                        $user = auth()->guard('chef')->user();
                        break;
                    case 'driver':
                        $user = auth()->guard('driver')->user();
                        break;
                }

                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'User not found.'], 404);
                }
                $otp = rand(1000, 9999);

                // Store OTP in the database
                Otp::updateOrCreate(
                    ['email' => $req->email],
                    ['otp_number' => $otp]
                );

                // Send OTP to the user's email
                if ($user->email) {
                    try {
                        if (config('services.is_mail_enable')) {
                            Mail::to($req->email)->send(new SendOtpToEmail($user, $otp));
                        }
                        return response()->json(['success' => true, 'message' => 'OTP has been sent to your email.'], 200);
                    } catch (\Exception $e) {
                        Log::error('Error sending OTP email: ' . $e->getMessage());
                        return response()->json(['success' => false, 'message' => 'Failed to send OTP. Please try again.'], 500);
                    }
                } else {
                    return response()->json(['success' => false, 'message' => 'User does not have a valid email.'], 422);
                }
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());;
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }




    // Helper function
    function getAuthenticatedUserType()
    {
        if (auth()->guard('user')->check()) {
            return 'user';
        } elseif (auth()->guard('chef')->check()) {
            return 'chef';
        } elseif (auth()->guard('driver')->check()) {
            return 'driver';
        }

        return null; // No authenticated user found
    }



    function verifyOtp(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'sometimes|email',
            'otp' => 'required|digits:4',
            'mobile' => 'sometimes',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Please provide a valid email.'], 400);
        }
        try {
            // For Mobile
            if ($req->mobile) {
                $verified = Otp::where(["mobile" => str_replace("-", "", $req->mobile), "otp_number" => $req->otp])->first();
                if ($verified) {
                    // Check if the OTP is expired (older than 2 minutes)
                    $otpCreatedAt = Carbon::parse($verified->updated_at);
                    $currentTime = Carbon::now();

                    // Check if the OTP is older than 2 minutes
                    // if ($otpCreatedAt->diffInMinutes($currentTime) > 2) {
                    //     return response()->json(['message' => "OTP has expired! Please request a new OTP.", 'success' => false], 400);
                    // }

                    return response()->json(['message' => ("Your " . ($req->mobile ? "Mobile No. " . $req->mobile : "account") . " has been verified."), 'success' => true], 200);
                } else {
                    return response()->json(['message' => "OTP is invalid ! Try again", "success" => false], 500);
                }
            }

            // Form Email
            if ($req->email) {
                $verified = Otp::where(["email" => $req->email, "otp_number" => $req->otp])->first();
                if (!$verified) {
                    return response()->json(['message' => "Invalid OTP.", 'success' => false], 400);
                }
                // Check if the OTP is expired (older than 5 minutes)
                $otpCreatedAt = Carbon::parse($verified->updated_at);
                $currentTime = Carbon::now();
                // Check if the OTP is older than 5 minutes
                if ($otpCreatedAt->diffInMinutes($currentTime) > 5) {
                    return response()->json(['message' => "OTP has expired! Please request a new OTP.", 'success' => false], 400);
                }

                $userType = $this->getAuthenticatedUserType();
                if (!$userType) {
                    return response()->json(['message' => 'Unauthenticated.'], 401);
                }

                // Identify the user based on the guard
                switch ($userType) {
                    case 'user':
                        $user = auth()->guard('user')->user();
                        break;
                    case 'chef':
                        $user = auth()->guard('chef')->user();
                        break;
                    case 'driver':
                        $user = auth()->guard('driver')->user();
                        break;
                }

                if (!$user) {
                    return response()->json(['success' => false, 'message' => 'User not found.'], 404);
                }
                if (isset($user->is_email_verified)) {
                    $user->is_email_verified = 1;
                }
                if (isset($user->email_verified_at) && $user->email_verified_at != null) {
                    return response()->json(['success' => true, 'message' => 'Email already verified.'], 200);
                }
                $user->email_verified_at = Carbon::now();
                $user->save();
                return response()->json(['message' => "Your email is verified successfully.", 'success' => true], 200);
            }
            return response()->json(['message' => "Invalid request.", 'success' => false], 500);

        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
