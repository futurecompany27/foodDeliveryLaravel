<?php

namespace App\Http\Controllers\drivers;

use App\Http\Controllers\Controller;
use App\Mail\DriverProfileStatusMail;
use App\Mail\HomeshefDriverChangeEmailLink;
use App\Mail\HomeshefDriverEmailVerificationLink;
use App\Mail\HomeshefDriverEmailVerifiedSuccessfully;
use App\Models\Admin;
use App\Models\Adminsetting;
use App\Models\Contact;
use App\Models\Driver;
use App\Models\DriverContact;
use App\Models\DriverProfileReviewByAdmin;
use App\Models\DriverRequestForUpdateDetail;
use App\Models\DriverScheduleCall;
use App\Models\DriverSuggestion;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\Otp;
use App\Models\Pincode;
use App\Models\SubOrders;
use App\Models\OrderTrackDetails;
use App\Notifications\admin\DriverRequestQueryNotification;
use App\Notifications\Driver\DriverSendReviewToAdmin;
use App\Notifications\Driver\DriverContactUsNotification;
use App\Notifications\Driver\driverRegisterationNotification;
use App\Notifications\Driver\DriverScheduleCallNotification;
use App\Notifications\Driver\DriverStatusUpdateNotification;
use App\Notifications\Driver\DriverSuggestionNotification;
use App\Notifications\Driver\AcceptOrderNotifyToAdmin;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Auth;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class DriverController extends Controller
{


    function driverRegisteraion(Request $req)
    {
        $checkPinCode = Pincode::where(['pincode' => substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3), 'status' => 1])->first();
        if (!$checkPinCode) {
            return response()->json(['message' => 'we are not offering our services in this region', 'ServiceNotAvailable' => true, 'success' => false], 500);
        }
        $validator = Validator::make($req->all(), [
            "firstName" => 'required',
            "lastName" => 'required',
            "email" => 'required',
            "mobileNo" => 'required',
            "are_you_a" => 'required',
            "password" => 'required',
            "full_address" => 'required',
            "province" => 'required',
            "city" => 'required',
            "latitude" => 'required',
            "longitude" => 'required',
            "postal_code" => 'required',
        ], [
            "firstName.required" => "Please fill email",
            "lastName.required" => "Please fill email",
            "email.required" => "Please fill email",
            "mobileNo.required" => "Please fill mobileNo",
            "are_you_a.required" => "Please fill select driver",
            "password.required" => "Please fill password",
            "full_address" => 'Please fill full_address',
            "province" => 'Please fill province',
            "city" => 'Please fill city',
            "latitude" => 'Please fill latitude',
            "longitude" => 'Please fill longitude',
            "postal_code" => 'Please fill postal_code',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driverExist = Driver::where('email', $req->email)->first();
            if ($driverExist) {
                return response()->json(["message" => 'Email already registered', "success" => false], 500);
            }
            $driverExist = Driver::where('mobileNo', str_replace("-", "", $req->mobileNo))->first();
            if ($driverExist) {
                return response()->json(["message" => 'Mobile No already registered', "success" => false], 500);
            }
            $driver = new Driver();
            $driver->firstName = $req->firstName;
            $driver->lastName = $req->lastName;
            $driver->email = $req->email;
            $driver->mobileNo = str_replace("-", "", $req->mobileNo);
            $driver->are_you_a = $req->are_you_a;
            $driver->password = Hash::make($req->password);
            $driver->full_address = $req->full_address;
            $driver->province = $req->province;
            $driver->city = $req->city;
            $driver->latitude = $req->latitude;
            $driver->longitude = $req->longitude;
            $driver->postal_code = strtoupper($req->postal_code);
            $driver->save();
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($req->email))->send(new HomeshefDriverEmailVerificationLink($driver));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new driverRegisterationNotification($driver));
            }
            $token = JWTAuth::fromUser($driver);
            DB::commit();
            return response()->json(["message" => 'Your registration has been successful', "success" => true, "driver_id" => $driver, 'token' => $token], 200);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function driverLogin(Request $req)
    {
        // Validate request data
        $validator = Validator::make($req->all(), [
            "userName" => 'required',
            "password" => 'required',
        ], [
            "userName.required" => "Please fill User Name",
            "password.required" => "Please fill password",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            // Determine if userName is email or mobile number
            $driver = Driver::where('email', $req->userName)
                ->orWhere('mobileNo', str_replace("-", "", $req->userName))
                ->first();

            if (!$driver) {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
            // Make password visible for verification
            $driver->makeVisible('password');

            if (!Hash::check($req->password, $driver->password)) {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
            // Hide password after verification
            $driver->makeHidden('password');

            // Attempt to generate token using email or mobileNo
            $credentials = filter_var($req->userName, FILTER_VALIDATE_EMAIL) ?
                ['email' => $req->userName, 'password' => $req->password] :
                ['mobileNo' => str_replace("-", "", $req->userName), 'password' => $req->password];

            if ($token = auth('driver')->attempt($credentials)) {
                return response()->json(['message' => 'Login Successfully!', 'driver_id' => auth()->guard('driver')->user()->id, 'token' => Driver::createToken($token), 'success' => true], 200);
            }

            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function driverProfile()
    {
        // Retrieve the authenticated driver
        $driver = auth()->guard('driver')->user();
        // Check if the driver is authenticated
        if (!$driver) {
            return response()->json(['message' => 'Driver not found', 'success' => false], 404);
        }
        // Return the driver's profile
        return response()->json(['success' => true, 'driver' => $driver], 200);
    }

    public function driverLogout()
    {
        auth()->guard('driver')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function driverRefreshToken()
    {
        try {
            // Get the current token
            $currentToken = JWTAuth::getToken();

            // Refresh the token
            $newToken = JWTAuth::refresh($currentToken);

            // Return the new token in the same format as in createToken
            return response()->json([
                'access_token' => $newToken,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 720, // 24 hours in seconds
                'success' => true,
                'message' => 'Token refreshed successfully!'
            ]);
        } catch (JWTException $e) {
            return response()->json(['success' => false, 'message' => 'Failed to refresh token, please try again'], 500);
        }
    }


    function driverForgetPassword(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "userName" => 'required',
            "password" => 'required',
            "confirm_password" => 'required|same:password',
        ], [
            "password.required" => "Enter the Password",
            "confirm_password.required" => "Enter the confirm password",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = Driver::where('email', $req->userName)->first();
            if (!$driver) {
                $driver = Driver::where('mobileNo', $req->userName)->first();
            }
            if (!$driver) {
                return response()->json(['message' => 'User not found', 'success' => false], 400);
            }
            Driver::where('email', $driver->email)->orWhere('mobileNo', $driver->mobileNo)->update(['password' => Hash::make($req->password)]);
            return response()->json(['message' => 'User Password updated', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updatePersonalDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "driver_id" => 'required',
            "firstName" => 'sometimes',
            "lastName" => 'sometimes',
            "full_address" => 'sometimes',
            "province" => 'sometimes',
            "city" => 'sometimes',
            "postal_code" => 'sometimes',
        ], [
            // "driver_id.sometimes" => "Please fill driver_id",
            "firstName.sometimes" => "Please fill firstName",
            "lastName.sometimes" => "Please fill lastName",
            "full_address.sometimes" => "Please fill full_address",
            "province.sometimes" => "Please fill province",
            "city.sometimes" => "Please fill city",
            "postal_code.sometimes" => "Please fill postal_code",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $path = "driver/" . $driver->id . '/';
            // $path = "driver/" . $req->driver_id . '/';
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $driver = Driver::find($driver->id);
            $update = [
                'firstName' => $req->firstName ?? $driver->firstName,
                'lastName' => $req->lastName ?? $driver->lastName,
                'full_address' => $req->full_address ?? $driver->full_address,
                'province' => $req->province ?? $driver->province,
                'city' => $req->city ?? $driver->province,
                'postal_code' => strtoupper($req->postal_code) ?? $driver->postal_code,
            ];
            if ($req->hasFile('profile_pic') || $req->hasFile('address_proof')) {
                // $driver = Driver::find($req->driver_id);
                $driver = auth()->guard('driver')->user();
            }
            if ($req->hasFile('profile_pic')) {
                if (file_exists(str_replace(env('filePath'), '', $driver->profile_pic))) {
                    unlink(str_replace(env('filePath'), '', $driver->profile_pic));
                }
                $storedPath = $req->file('profile_pic')->store($path, 'public');
                $update['profile_pic'] = asset('storage/' . $storedPath);
            }

            if ($req->hasFile('address_proof')) {
                if (file_exists(str_replace(env('filePath'), '', $driver->address_proof))) {
                    unlink(str_replace(env('filePath'), '', $driver->address_proof));
                }
                $storedPath = $req->file('address_proof')->store($path, 'public');
                $update['address_proof'] = asset('storage/' . $storedPath);
            }

            Log::info('This is update', [$update]);
            Driver::where('id', $driver->id)->update($update);
            // Driver::where('id', $req->driver_id)->update($update);
            return response()->json(['message' => 'Your information has been updated', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function updateDrivingLicence(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "id" => 'required',
            "driving_licence_no" => ['required', 'regex:/^[A-Z][0-9]{4} [0-9]{6} [0-9]{2}$/'],
        ], [
            // "id.required" => "Please fill password",
            "driving_licence_no.required" => "Please fill driving licence no.",
            "driving_licence_no.regex" => "Invalid driving licence number format. Please enter a valid format like 'A3567 678907 45'.",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $path = 'driver/' . $driver->id;
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            if ($req->hasFile('driving_licence_proof')) {
                $driver = auth()->guard('driver')->user();
                // $driver = Driver::find($req->id);
                if (file_exists(str_replace(env('filePath'), '', $driver->driving_licence_proof))) {
                    unlink(str_replace(env('filePath'), '', $driver->driving_licence_proof));
                }
                $storedPath = $req->file('driving_licence_proof')->store($path, 'public');
                Driver::where('id', $driver->id)->update(['driving_licence_proof' => asset('storage/' . $storedPath), 'status' => 0]);
            }
            Driver::where('id', $driver->id)->update(['driving_licence_no' => $req->driving_licence_no]);
            return response()->json(['message' => 'Your license details has been updated', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function updateTaxationNo(Request $req)
    {
        // $validator = Validator::make($req->all(), [
        //     "id" => 'required',
        // ], [
        //     "id.required" => "Please fill id",
        // ]);
        // if ($validator->fails()) {
        //     return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        // }
        if (!File::exists("public/storage/driver/TaxInformation")) {
            File::makeDirectory("public/storage/driver/TaxInformation", $mode = 0777, true, true);
        }

        try {
            DB::beginTransaction();
            $driver = auth()->guard('driver')->user();
            // Retrieve the chef's data
            // $driver = Driver::find($req->id);

            if (!$driver) {
                return response()->json(['message' => 'Driver not found', 'success' => false], 404);
            }

            // Delete existing GST image if it exists
            if ($req->hasFile('gst_image') && $driver->gst_image) {
                $existingGstImagePath = public_path(str_replace(asset('storage/'), '', $driver->gst_image));
                if (File::exists($existingGstImagePath)) {
                    // Attempt to delete the file
                    if (!unlink($existingGstImagePath)) {
                        return response()->json(['message' => 'Failed to delete existing GST image', 'success' => false], 500);
                    }
                }
            }

            // Delete existing QST image if it exists
            if ($req->hasFile('qst_image') && $driver->qst_image) {
                $existingQstImagePath = public_path(str_replace(asset('storage/'), '', $driver->qst_image));
                if (File::exists($existingQstImagePath)) {
                    // Attempt to delete the file
                    if (!unlink($existingQstImagePath)) {
                        return response()->json(['message' => 'Failed to delete existing QST image', 'success' => false], 500);
                    }
                }
            }

            // Store new GST image
            if ($req->hasFile('gst_image')) {
                $filename = $req->file('gst_image')->store('/driver/TaxInformation');
                $driver->gst_image = asset('storage/' . $filename);
            }

            // Store new QST image
            if ($req->hasFile('qst_image')) {
                $filenames = $req->file('qst_image')->store('/driver/TaxInformation');
                $driver->qst_image = asset('storage/' . $filenames);
            }

            // Update GST and QST numbers
            $driver->gst_no = $req->gst_no;
            $driver->qst_no = $req->qst_no;
            $driver->save();

            DB::commit();
            return response()->json(['message' => "Tax Information Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // function updateAddress(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //         "full_address" => 'required',
    //         "province" => 'required',
    //         "city" => 'required',
    //         "postal_code" => 'required',
    //     ], [
    //         "id.required" => "Please fill password",
    //         "full_address.required" => "Please fill driving licence no",
    //         "province.required" => "Please fill driving licence no",
    //         "city.required" => "Please fill driving licence no",
    //         "postal_code.required" => "Please fill driving licence no",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $path = 'driver/' . $req->id;
    //         if ($req->hasFile('address_proof')) {
    //             $driver = Driver::find($req->id);
    //             if (file_exists(str_replace(env('filePath'), '', $driver->address_proof))) {
    //                 unlink(str_replace(env('filePath'), '', $driver->address_proof));
    //             }
    //             $storedPath = $req->file('address_proof')->store($path, 'public');
    //             Driver::where('id', $req->id)->update(['address_proof' => asset('storage/' . $storedPath), 'status' => 0]);
    //         }
    //         Driver::where('id', $req->id)->update([
    //             'full_address' => $req->full_address,
    //             'province' => $req->province,
    //             'city' => $req->city,
    //             'postal_code' => $req->postal_code
    //         ]);
    //         return response()->json(['message' => 'updated successfully', 'success' => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }


    function updateCriminialReport(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "criminal_report" => 'required',
        ], [
            "criminal_report.required" => "Please fill criminal report",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = 'driver/' . $req->id;
            if ($req->hasFile('criminal_report')) {
                // $driver = Driver::find($req->id);
                $driver = auth()->guard('driver')->user();
                if (file_exists(str_replace(env('filePath'), '', $driver->criminal_report))) {
                    unlink(str_replace(env('filePath'), '', $driver->criminal_report));
                }
                $storedPath = $req->file('criminal_report')->store($path, 'public');
                Driver::where('id', $driver->id)->update(['criminal_report' => asset('storage/' . $storedPath), 'status' => 0]);
            }
            return response()->json(['message' => 'updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateDriverBankDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "id" => 'required',
            "bank_name" => 'required',
            "transit_number" => 'required',
            "account_number" => 'required',
            "institution_number" => 'required',
        ], [
            // "id.required" => "Please fill id",
            "bank_name.required" => "Please fill bank name",
            "transit_number.required" => "Please fill driving transit number",
            "account_number.required" => "Please fill driving account number",
            "institution_number.required" => "Please fill driving institution number",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $update = [
                'bank_name' => $req->bank_name,
                'transit_number' => $req->transit_number,
                'account_number' => $req->account_number,
                'institution_number' => $req->institution_number,
            ];
            Driver::where('id', $driver->id)->update($update);
            return response()->json(["message" => 'Bank details has been updated successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function driverScheduleAnCall(Request $req)
    {
        if (!$req->date || !$req->slot) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $slotNotAvailable = DriverScheduleCall::where(['date' => $req->date, 'slot' => $req->slot])->first();
            if ($slotNotAvailable) {
                return response()->json(['message' => 'Slot not available select another slot', 'success' => false], 500);
            }

            $SameChefSameSlot = DriverScheduleCall::where(['driver_id' => $driver->id, 'date' => $req->date, 'slot' => $req->slot])->first();
            if ($SameChefSameSlot) {
                return response()->json(['message' => 'Already booked on same slot', 'success' => false], 500);
            }

            $scheduleNewCall = new DriverScheduleCall();
            $scheduleNewCall->driver_id = $driver->id;
            $scheduleNewCall->date = $req->date;
            $scheduleNewCall->slot = $req->slot;
            $scheduleNewCall->save();


            $ScheduleCall = DriverScheduleCall::orderBy('created_at', 'desc')->where('driver_id', $driver->id)->with('driver')->first();

            Log::info($ScheduleCall);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverScheduleCallNotification($ScheduleCall));
            }

            return response()->json(["message" => 'Call has been scheduled successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function AddDriverContactData(Request $req)
    {
        // if (!$req->driver_id) {
        //     return response()->json(["message" => "Please fill all the required fields ", "success" => false], 400);
        // }
        try {
            $driver = auth()->guard('driver')->user();
            $contact = new DriverContact();
            $contact->driver_id = $driver->id;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();
            $contactUs = DriverContact::orderBy('created_at', 'desc')->where('driver_id', $driver->id)->with('driver')->first();

            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverContactUsNotification($contactUs));
            }
            return response()->json(['message' => 'Submitted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getMyDetails(Request $req)
    {
        // if (!$req->driver_id) {
        //     return response()->json(["message" => "Please fill all the required fields ", "success" => false], 400);
        // }
        try {
            $driver = auth()->guard('driver')->user();
            // $driver = Driver::find($req->driver_id);
            // if ($driver->status == 0) {
            //     return response()->json(["message" => "Driver is Inactive ", "success" => false], 400);
            // }
            return response()->json(['data' => $driver, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function driverUpdateEmail(Request $req)
    {
        // if (!$req->driver_id || !$req->email) {
        $validator = Validator::make($req->all(), [
            "email" => 'required|email',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            Driver::where('id', $driver->id)->update(['email' => $req->email, 'status' => 0]);
            // $driver = Driver::find($req->driver_id);
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($req->email))->send(new HomeshefDriverChangeEmailLink($driver));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            return response()->json(['message' => 'Your email has been updated', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function VerifyDriverEmail(Request $req)
    {
        // if (!$req->id) {
        //     return response()->json(["message" => 'Please fill all the details', "success" => false], 400);
        // }
        try {
            $driver = auth()->guard('driver')->user();
            $checkVerification = Driver::find($driver->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                Driver::where('id', $driver->id)->update(['email_verified_at' => Carbon::now(), 'is_email_verified' => 1]);
                $driverDetails = Driver::find($driver->id);
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($driverDetails->email))->send(new HomeshefDriverEmailVerifiedSuccessfully($driverDetails));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
                return response()->json(['message' => 'Email has been verified successfully', 'success' => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }


    function updateLatLongAndGetListOfOrdersForDriver(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "latitude" => 'required',
            "longitude" => 'required',
        ], [
            "latitude.required" => "Please fill latitude",
            "longitude.required" => "Please fill longitude",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $driver = auth()->guard('driver')->user();
            if ($driver instanceof Driver) {
                $currentLat = $req->latitude;
                $currentLong = $req->longitude;

                // Cache key to store data
                $cacheKey = 'driver_' . $driver->id . '_data';
                $cacheData = Cache::get($cacheKey);

                // Check if cache data exists and coordinates match
                if ($cacheData && $cacheData['latitude'] == $currentLat && $cacheData['longitude'] == $currentLong && $cacheData['driver_id'] == $driver->id) {
                    return response()->json([
                        'message' => 'Here is your order list from cache',
                        'count' => $cacheData['count'],
                        'data' => $cacheData['data'],
                        'success' => true,
                    ], 200);
                }

                $admin_setting = Adminsetting::first();
                $radius = $admin_setting->radius != "" ? $admin_setting->radius : 1;
                Log::info('Radius', [$radius]);
                $driver->update(['latitude' => $currentLat, 'longitude' => $currentLong]);

                // Fetch orders with accepted suborders
                $getAllOrdersWithAcceptedSuborder = Order::where('payment_status', 'paid')
                    ->when(isset($req->delivery_date), function ($query) use ($req) {
                        return $query->where('delivery_date', $req->delivery_date);
                    }, function ($query) {
                        // If delivery_date is not provided, filter for today's date
                        return $query->where('delivery_date', str_replace('-', '/', Carbon::now()->format('d-m-Y')));
                    })
                    ->orderBy('delivery_date', 'asc')
                    ->with([
                        'subOrders' => function ($query) {
                            $query->where('status', '3')->whereNull('driver_id')->with('chefs');
                        }
                    ])->get();
                Log::info('getAllOrder', [$getAllOrdersWithAcceptedSuborder]);

                $gmApiK = env('GOOGLE_MAP_KEY');
                $origin = $currentLat . ',' . $currentLong;

                $pendingSubOrders = [];

                foreach ($getAllOrdersWithAcceptedSuborder as $orders) {
                    $sub_orders = $orders->subOrders;
                    foreach ($sub_orders as $sub_order_of_chef) {
                        $destination = $sub_order_of_chef->chefs->latitude . ',' . $sub_order_of_chef->chefs->longitude;

                        try {
                            $apiUrl = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&alternatives=true&sensor=false&departure_time=now&key=" . $gmApiK;
                            Log::info('ApiUrl', [$apiUrl]);
                            $routes = json_decode(file_get_contents($apiUrl))->routes;
                            Log::info('Routes', [$routes]);
                            if (count($routes) != 0) {
                                $dist = $routes[0]->legs[0]->distance->text;
                                $distance = floatval(explode(" ", $dist)[0]);
                                $duration = $routes[0]->legs[0]->duration->text;

                                // Check if the distance is within the defined radius
                                if ($distance <= $radius) {
                                    $pendingSubOrders[] = [
                                        "sub_order_id" => $sub_order_of_chef->sub_order_id,
                                        "distance" => $distance,
                                        "duration" => $duration,
                                    ];
                                }
                            }
                        } catch (\Exception $e) {
                            Log::error('Error fetching Google Maps directions: ' . $e->getMessage());
                        }
                    }
                }

                // Get suborder details
                $data = SubOrders::whereIn('sub_order_id', array_column($pendingSubOrders, 'sub_order_id'))
                    ->with(['Orders', 'chefs', 'OrderItems', 'OrderTrack'])
                    ->orderBy('created_at', 'desc')->get();

                $count = $data->count();

                $mappedData = $data->map(function ($order) use ($pendingSubOrders) {
                    $orderData = $order->toArray();
                    $matchedOrder = Arr::first($pendingSubOrders, function ($item) use ($order) {
                        return $item["sub_order_id"] == $order->sub_order_id;
                    });
                    if ($matchedOrder) {
                        $orderData["distance"] = $matchedOrder["distance"];
                        $orderData["duration"] = $matchedOrder["duration"];
                    }
                    return $orderData;
                });

                // Store the new data in the cache
                $newCacheData = [
                    'latitude' => $currentLat,
                    'longitude' => $currentLong,
                    'driver_id' => $driver->id,
                    'count' => $count,
                    'data' => $mappedData
                ];

                Cache::put($cacheKey, $newCacheData, now()->addMinutes(1)); // Set the cache to expire after 1 minute

                return response()->json([
                    'message' => 'Here is your order list',
                    'count' => $count,
                    'data' => $mappedData,
                    'success' => true,
                ], 200);
            } else {
                return response()->json(['error' => 'Unauthorized access', 'success' => false], 400);
            }
        } catch (\Exception $th) {
            Log::error('Error: ' . $th->getMessage());
            return response()->json(['error' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }




    public function getAllDriver(Request $req)
    {
        try {
            $totalRecords = Driver::count();
            if ($req->list) {
                $data = Driver::select('id', 'firstName', 'lastName')->get();
            } else {
                $skip = $req->page * 10;
                $data = Driver::orderBy('created_at', 'desc')
                // ->skip($skip)->take(10)
                ->get();
                // $data = User::orderBy('created_at', 'desc')->paginate(10);
            }
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
                'success' => true
            ], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function deleteDriver(Request $request)
    {
        try {
            $driver = Driver::find($request->id);
            $driver->delete();

            return response()->json(['message' => 'Delete driver successfully', 'success' => true, 'data' => $driver], 200);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addDriverContact(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'subject' => 'required|max:100',
            'message' => 'required|max:500',
        ], [
            'subject.required' => 'Please fill subject',
            'message.required' => 'Please fill message',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            DB::beginTransaction();
            $contact = [
                'driver_id' => $driver->id,
                'subject' => $req->subject,
                'message' => $req->message,
            ];
            DriverContact::create($contact);

            DB::commit();
            return response()->json(['message' => 'Query submitted successfully', 'success' => true, 'data' => $contact], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getDriverContact(Request $req)
    {
        try {
            if ($req->has('id')) {

                $driver = Driver::find($req->id);
                if (!$driver) {
                    return response()->json(['message' => 'Driver not found.', 'success' => false], 404);
                }

                $driverContact = DriverContact::with('driver:id,firstName,lastName,email,mobileNo')->where('driver_id', $req->id)->get();

                return response()->json(['message' => 'Driver query fetched successfully', 'success' => true, 'data' => $driverContact], 200);
            }

            $driverContact = DriverContact::with('driver:id,firstName,lastName,email,mobileNo')->orderBy('created_at', 'desc')->get();

            return response()->json(['message' => 'Contacts fetched successfully', 'success' => true, 'data' => $driverContact], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // public function addDriverSuggestions(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "driver_id" => 'required|exists:drivers,id',
    //     ], [
    //         "driver_id.required" => "Please fill driver id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     if (!file_exists('storage/driver/suggestions')) {
    //         mkdir('storage/driver/suggestions', 0755, true);
    //     }
    //     try {
    //         DB::beginTransaction();
    //         $storedPath = $req->file('sample_pic')->store('driver/suggestions', 'public');
    //         $filename = asset('/storage', $storedPath);

    //         $driverSuggestion = new DriverSuggestion();
    //         $driverSuggestion->related_to = $req->related_to;
    //         $driverSuggestion->message = $req->message;
    //         $driverSuggestion->sample_pic = $filename;
    //         $driverSuggestion->driver_id = $req->driver_id;
    //         $driverSuggestion->save();

    //         $driver = Driver::find($req->driver_id);
    //         $driverDetail['id'] = $driver->id;
    //         $driverDetail['firstName'] = $driver->firstName;
    //         $driverDetail['lastName'] = $driver->lastName;

    //         $admins = Admin::all(['*']);
    //         foreach ($admins as $admin) {
    //             $admin->notify(new DriverSuggestionNotification($driverDetail));
    //         }
    //         // $suggestion = DriverSuggestion::find($req->id)->with('driver:id,firstName,lastName')->get();

    //         DB::commit();
    //         return response()->json(['message' => 'Suggestion Added successfully', "success" => true, 'data'=> $driverSuggestion], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }



    public function addDriverSuggestions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "driver_id" => 'required|exists:drivers,id',
            "sample_pic" => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048', // Adjusted validation for sample_pic
            "related_to" => "required",
            "message" => "required",
        ], [
            // "driver_id.required" => "Please provide driver ID",
            "sample_pic.image" => "The sample picture must be an image file",
            "sample_pic.mimes" => "The sample picture must be a valid image file (jpeg, png, jpg, gif)",
            "sample_pic.max" => "The sample picture size must not exceed 2MB",
            "related_to.required" => "Please provide a related_to field",
            "message.required" => "Please provide a message",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $driver = auth()->guard('driver')->user();
            DB::beginTransaction();

            // Store the sample picture
            $storedPath = $req->file('sample_pic')->store('driver/suggestions', 'public');
            $filename = asset('storage/' . $storedPath);

            // Create a new driver suggestion
            $driverSuggestion = new DriverSuggestion();
            $driverSuggestion->related_to = $req->related_to;
            $driverSuggestion->message = $req->message;
            $driverSuggestion->sample_pic = $filename;
            $driverSuggestion->driver_id = $driver->id;
            $driverSuggestion->save();

            // Notify admins about the new driver suggestion
            // $driver = Driver::find($driver->id);
            $driverDetail = [
                'id' => $driver->id,
                'firstName' => $driver->firstName,
                'lastName' => $driver->lastName,
            ];

            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new DriverSuggestionNotification($driverDetail));
            }

            DB::commit();

            return response()->json(['message' => 'Suggestion added successfully', "success" => true, 'data' => $driverSuggestion], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function getDriverSuggestions(Request $req)
    {
        try {
            if ($req->has('id')) {
                $id = $req->input('id');
                $suggestions = DriverSuggestion::with('driver')->findOrFail($id);
            } else {
                $suggestions = DriverSuggestion::with('driver')->get();
            }

            return response()->json([
                'message' => 'Driver suggestions retrieved successfully!',
                'success' => true,
                'data' => $suggestions,
            ], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json([
                'message' => 'Oops! Something went wrong.',
                'success' => false,
                'data' => [],
            ], 500);
        }
    }

    function updateDriverProfileStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required|exists:drivers,id',
            "status" => 'required',
        ], [
            "id.required" => "Please fill driver id",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Driver::where('id', $req->id)->update(['status' => $req->status]);
            $driverDetail = Driver::find($req->id);
            Log::info($driverDetail);
            $driverDetail->notify(new DriverStatusUpdateNotification($driverDetail));
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($driverDetail->email))->send(new DriverProfileStatusMail($driverDetail));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function sendDriverProfileForReview(Request $req)
    {
        try {
            $driver = auth()->guard('driver')->user();
            $newProfileForReview = new DriverProfileReviewByAdmin();
            $newProfileForReview->driver_id = $driver->id;
            $newProfileForReview->save();
            Driver::where('id', $driver->id)->update(['status' => 2]);
            DriverRequestForUpdateDetail::where(['driver_id' => $driver->id, 'status' => 1])->update(['status' => 2]);

            // $driver = Driver::where('id', $req->driver->id)->first();
            // Log::info($chef);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverSendReviewToAdmin($driver));
            }

            return response()->json(['message' => 'Request Submitted successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function driverRequestForUpdate(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'request_for' => 'required',
            'message' => 'required',
        ], [
            'request_for.required' => 'Please select a request type.',
            'message.required' => 'Please provide a message for your request.',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $alreadyPending = DriverRequestForUpdateDetail::orderBy('created_at', 'desc')->where('driver_id', $driver->id)->whereIn('status', [0, 1])->first();
            if ($alreadyPending) {
                return response()->json(['message' => 'Action cannot be completed . Please wait till you previous request is processed.', 'success' => false], 500);
            }
            $newRequest = new DriverRequestForUpdateDetail();
            $newRequest->driver_id = $driver->id;
            $newRequest->request_for = $req->request_for;
            $newRequest->message = $req->message;
            $newRequest->save();
            Log::info('newRequest', [$newRequest]);

            $driverRequestQuery = DriverRequestForUpdateDetail::orderBy('created_at', 'desc')
                ->where('driver_id', $driver->id)
                ->with('driver:id,firstName,lastName')->first();
            // Log::info($driverRequest);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverRequestQueryNotification($driverRequestQuery));
            }
            return response()->json(['message' => 'Your request has been sent to Homeplate. It will be processed shortly.', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function getDriverFinalReview(Request $req)
    {
        try {
            $driverReviews = DriverProfileReviewByAdmin::with('driver')->get();

            return response()->json(['message' => 'Driver review requests fetched successfully.', 'success' => true, 'data' => $driverReviews], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    // Driver Started Order
    public function driverCurrentOrder(Request $req)
    {
        try {
            $driver = auth()->guard('driver')->user();
            Log::info('driver', [$driver->id]);

            // Fetch the current order for the driver
            $subOrder = SubOrders::with(['Orders', 'OrderItems', 'chefs'])
                ->where('driver_id', $driver->id)
                ->whereIn('status', ['3', '7', '8']) // Assuming these statuses mean ongoing orders
                ->first();
            if ($subOrder->status >= 3 && $subOrder->status < 9) {
                $subOrder->makeVisible(['pickup_token']);
            }

            // Check if there is no current order
            if (!$subOrder) {
                return response()->json(["message" => "No current order found.", "success" => false, "data" => []], 200);
            }

            // Get Google Maps API key
            $gmApiK = env('GOOGLE_MAP_KEY');
            $driverLat = $driver->latitude;
            $driverLong = $driver->longitude;

            // Check if the chef's latitude and longitude are available
            $chef = $subOrder->chefs;
            if ($chef && $chef->latitude && $chef->longitude) {
                $chefLat = $chef->latitude;
                $chefLong = $chef->longitude;

                // Call the Google Maps Directions API to calculate distance and duration
                try {
                    $apiUrl = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode("$driverLat,$driverLong") . "&destination=" . urlencode("$chefLat,$chefLong") . "&key=" . $gmApiK;
                    $routes = json_decode(file_get_contents($apiUrl));

                    if (!empty($routes->routes)) {
                        // Get distance and duration from the API response
                        $distanceText = $routes->routes[0]->legs[0]->distance->text;
                        $durationText = $routes->routes[0]->legs[0]->duration->text;

                        // Add distance and duration to the subOrder object
                        $subOrder->distance = $distanceText;
                        $subOrder->duration = $durationText;
                    } else {
                        // Handle case when no routes are returned
                        $subOrder->distance = "Not available";
                        $subOrder->duration = "Not available";
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching distance and duration: ' . $e->getMessage());
                    $subOrder->distance = "Error calculating distance";
                    $subOrder->duration = "Error calculating duration";
                }
            } else {
                // Handle case when the chef's location is not available
                $subOrder->distance = "Chef location not available";
                $subOrder->duration = "Chef location not available";
            }

            // Return the response with the current subOrder
            return response()->json(["message" => "Fetched current order successfully", "success" => true, 'data' => $subOrder], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching driver’s current orders: ' . $e->getMessage());
            return response()->json(["message" => "Oops! Something went wrong.", "success" => false], 500);
        }
    }


    // Driver's All Accepted Orders
    // public function driverAcceptedOrder(Request $req)
    // {
    //     try {
    //         $driver = auth()->guard('driver')->user();
    //         $subOrders = SubOrders::with(['Orders', 'OrderItems', 'chefs'])->where('driver_id', $driver->id)->where('status', '=', '2')->orderByDesc('id')->get();

    //         $subOrders->each(function ($subOrder) {
    //             $subOrder->makeHidden(['customer_delivery_token', 'pickup_token']);
    //         });
    //         if ($subOrders->isEmpty()) {
    //             return response()->json(["message" => "No ongoing orders found.", "success" => true, "data" => []], 200);
    //         }
    //         return response()->json(["message" => "Fetched ongoing orders successfully", "success" => true, 'data' => $subOrders ?? []], 200);
    //     } catch (\Exception $e) {
    //         Log::error('Error fetching driver’s current orders: ' . $e->getMessage());
    //         return response()->json(["message" => "Oops! Something went wrong.", "success" => false], 500);
    //     }
    // }


    public function driverAcceptedOrder(Request $req)
    {
        try {
            $driver = auth()->guard('driver')->user();

            // Ensure the driver's location is available
            if (!$driver->latitude || !$driver->longitude) {
                return response()->json(["message" => "Driver's location not set.", "success" => false], 400);
            }

            // Fetch the suborders for the driver
            $subOrders = SubOrders::with(['Orders', 'OrderItems', 'chefs'])
                ->where('driver_id', $driver->id)
                ->where('status', '=', '2') // Assuming '2' means accepted
                ->orderByDesc('id')
                ->get();

            $subOrders->each(function ($subOrder) {
                $subOrder->makeHidden(['customer_delivery_token', 'pickup_token']);
            });

            // Check if there are no ongoing orders
            if ($subOrders->isEmpty()) {
                return response()->json(["message" => "No ongoing orders found.", "success" => true, "data" => []], 200);
            }

            // Calculate distance and duration for each suborder
            $gmApiK = env('GOOGLE_MAP_KEY'); // Get your Google Maps API Key
            $driverLat = $driver->latitude;
            $driverLong = $driver->longitude;

            foreach ($subOrders as $subOrder) {
                $chefLat = $subOrder->chefs->latitude;
                $chefLong = $subOrder->chefs->longitude;

                // Call the Google Maps Directions API
                try {
                    $apiUrl = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode("$driverLat,$driverLong") . "&destination=" . urlencode("$chefLat,$chefLong") . "&key=" . $gmApiK;
                    $routes = json_decode(file_get_contents($apiUrl));

                    if (!empty($routes->routes)) {
                        // Get distance and duration from the response
                        $distanceText = $routes->routes[0]->legs[0]->distance->text;
                        $durationText = $routes->routes[0]->legs[0]->duration->text;

                        // Store these values in the subOrder or an array for the response
                        $subOrder->distance = $distanceText; // Add distance to the suborder
                        $subOrder->duration = $durationText; // Add duration to the suborder
                    } else {
                        // Handle case when no routes are returned
                        $subOrder->distance = "Not available";
                        $subOrder->duration = "Not available";
                    }
                } catch (\Exception $e) {
                    Log::error('Error fetching distance and duration: ' . $e->getMessage());
                    $subOrder->distance = "Error calculating distance";
                    $subOrder->duration = "Error calculating duration";
                }
            }

            // Return the updated subOrders with distance and duration
            return response()->json(["message" => "Fetched ongoing orders successfully", "success" => true, 'data' => $subOrders], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching driver’s current orders: ' . $e->getMessage());
            return response()->json(["message" => "Oops! Something went wrong.", "success" => false], 500);
        }
    }


    // Driver's Delivered Orders
    public function driverCompletedOrder(Request $req)
    {
        try {
            $driver = auth()->guard('driver')->user();
            $query = SubOrders::query()
                ->with('Orders', 'OrderItems.foodItem', 'chefs')
                ->where('driver_id', $driver->id)
                ->where('status', '9')
                ->whereHas('Orders', function ($subQuery)  use ($req) {
                    $subQuery->where('payment_status', 'paid');
                    // Add filters for delivery_date between start_date and end_date
                    if ($req->start_date && $req->end_date) {
                        $subQuery->whereBetween('delivery_date', [$req->start_date, $req->end_date]);
                    } elseif ($req->start_date) {
                        $subQuery->where('delivery_date', '>=', $req->start_date);
                    } elseif ($req->end_date) {
                        $subQuery->where('delivery_date', '<=', $req->end_date);
                    }
                });
            // Executing the query
            $subOrders = $query->orderByDesc('created_at')->get();
            $count = count($subOrders);

            // Log the fetched data
            Log::info('Driver Suborders Array :', $subOrders->toArray());
            Log::info('Driver Suborder: ', [$query]);

            // $subOrders = SubOrders::with(['Orders', 'OrderItems', 'chefs'])->where(['driver_id' => $driver->id, 'status' => '9'])->orderByDesc('id')->get();

            // Check if result is empty
            if ($subOrders->isEmpty()) {
                return response()->json(["message" => "No completed orders found.", "success" => true, "data" => []], 200);
            }

            return response()->json(["message" => "Fetched completed orders successfully", "success" => true, 'count' => $count, 'data' => $subOrders], 200);
        } catch (\Exception $e) {
            Log::error('Error fetching driver’s completed orders: ' . $e->getMessage());
            return response()->json(["message" => "Oops! Something went wrong.", "success" => false], 500);
        }
    }

    // Driver Change Order Status
    public function driverChangeOrderStatus(Request $req)
    {
        Log::info('Request Log: ', [$req->all()]);
        $validator = Validator::make(
            $req->all(),
            [
                'suborder_id' => 'required|exists:sub_orders,id',
                'reason' => 'nullable|string|max:150',
                'status' => 'required|in:1,2,3,7,8,9',
                'pickup_otp' => 'required_if:status,7|string|digits:4',
                'delivery_otp' => 'sometimes|string|digits:4',
                'delivery_proof' => 'sometimes|mimes:jpg,png,jpeg|max:2048',
            ],
            [
                'pickup_otp.required_if' => 'The OTP field is required',
                'delivery_otp.sometimes' => 'Please enter valid OTP'
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driver = auth()->guard('driver')->user();
            $subOrder = SubOrders::with('Orders')->findOrFail($req->suborder_id);
            $trackStatus = OrderStatus::where('id', $req->status)->first()->status;
            $orderStatusMessages = [
                'order' => "$driver->firstName cancelled the order to deliver",
                'pending' => 'Your request is currently under review; please wait patiently', // id is 2
                'approve' => 'Order has been approved for pickup.', // id is 3
                'picked up' => 'Your order has been picked up by the driver.', // id is 7
                'on the way' => 'Your order is on the way.', // id is 8
                'delivered' => "Your order has been delivered.", // id is 9
            ];
            // if ($subOrder->status == '9' && $req->status == '9') {
            //     return response()->json(['message' => 'Order Closed.', 'success' => false, 'data' => ''], 400);
            // }
            // Check if the status exists in our predefined messages
            if (!isset($orderStatusMessages[$trackStatus])) {
                return response()->json(['message' => 'Invalid status.', 'success' => false, 'data' => ''], 400);
            }
            // driver cancel to deliver that order
            if ($req->status == '1') {
                $subOrder->driver_id = null;
                $subOrder->status = 3; // make status "cancel" so another driver can accept
                $subOrder->driver_accept_date_time = null;
                $subOrder->reason = null;
                $subOrder->save();

                $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);

                return response()->json(['message' => 'Order rejected successfully.', 'success' => true, 'data' => ''], 200);
            }
            // driver add order in upcoming list
            if ($req->status == '2') {
                $subOrder->driver_id = $driver->id;
                $subOrder->status = $req->status; // make status "pending" so another driver can approve
                $subOrder->driver_accept_date_time = Carbon::now();
                $subOrder->reason = $req->reason ?? null;
                $subOrder->save();
                $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);
                $driverDetail = Driver::find($driver->id);
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new AcceptOrderNotifyToAdmin($subOrder, $driverDetail));
                }

                return response()->json(['message' => 'Order pending successfully.', 'success' => true, 'data' => ''], 200);
            }
            // driver start order delivery
            if ($req->status == '3') {
                $currentTask = SubOrders::where('driver_id', $driver->id)->whereIn('status', ['3', '7', '8'])->first();
                if ($currentTask) {
                    return response()->json(['message' => 'Complete the task in your queue before accepting new ones.', 'success' => true, 'data' => ''], 200);
                }
                $subOrder->driver_id = $driver->id;
                $subOrder->status = $req->status; // make status "approve"
                $subOrder->driver_accept_date_time = Carbon::now();
                $subOrder->reason = $req->reason ?? null;
                $subOrder->save();
                $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);

                return response()->json(['message' => 'Task stated.', 'success' => true, 'data' => ''], 200);
            }
            // driver picked up order
            if ($req->status == '7') {
                if ($req->pickup_otp == $subOrder->pickup_token) {
                    $subOrder->driver_id = $driver->id;
                    $subOrder->status = $req->status; // make status "approve"
                    $subOrder->driver_accept_date_time = Carbon::now();
                    $subOrder->reason = $req->reason ?? null;
                    $subOrder->save();
                    $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);
                    // $this->storeDriverTracker($subOrder->track_id, $statusName, $statusMessage);

                    return response()->json(['message' => "Order $trackStatus successfully.", 'success' => true, 'data' => ''], 200);
                } else {
                    return response()->json(['message' => 'Invaid OTP', 'success' => false, 'data' => ''], 400);
                }
            }
            // driver on-the-way
            if ($req->status == '8') {
                $subOrder->driver_id = $driver->id;
                $subOrder->status = $req->status; // make status "approve"
                $subOrder->driver_accept_date_time = Carbon::now();
                $subOrder->save();
                $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);

                return response()->json(['message' => "Order is $trackStatus.", 'success' => true, 'data' => ''], 200);
            }
            // driver successfully delivered order
            if ($req->status == '9') {
                if (!file_exists('storage/driver/delivery_proof')) {
                    mkdir('storage/driver/delivery_proof', 0755, true);
                }
                // customer selected deliver to me only so OTP and Proof both required
                if ($subOrder->Orders->delivery_option == '1') {
                    if (!$req->delivery_proof) {
                        return response()->json(['message' => "Please update delivery proof.", 'success' => true, 'data' => ''], 200);
                    }

                    // store address proof
                    if ($req->hasFile('delivery_proof')) {
                        $storedPath = $req->file('delivery_proof')->store('driver/delivery_proof', 'public');
                        // Generate a URL to access the stored file
                        $filename = Storage::url($storedPath);
                    }

                    if ($req->delivery_otp == $subOrder->customer_delivery_token) {
                        $subOrder->driver_id = $driver->id;
                        $subOrder->status = $req->status;
                        $subOrder->delivery_proof_img = $filename;
                        $subOrder->driver_accept_date_time = Carbon::now();
                        $subOrder->save();
                        $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);
                        return response()->json(['message' => "Order $trackStatus successfully.", 'success' => true, 'data' => ''], 200);
                    } else {
                        return response()->json(['message' => 'Please provide OTP.', 'success' => false, 'data' => ''], 400);
                    }
                } else {
                    // Proof is required and OTP is (optional)
                    if ($req->delivery_otp != null && $req->delivery_otp != $subOrder->customer_delivery_token) {
                        return response()->json(['message' => 'Invalid OTP.', 'success' => false, 'data' => ''], 400);
                    }
                    if (!$req->hasFile('delivery_proof')) {
                        return response()->json(['message' => 'Upload delivery proof.', 'success' => false, 'data' => ''], 400);
                    } else {
                        $storedPath = $req->file('delivery_proof')->store('driver/delivery_proof', 'public');
                        // Generate a URL to access the stored file
                        $filename = Storage::url($storedPath);
                    }
                    $subOrder->driver_id = $driver->id;
                    $subOrder->status = $req->status; // make status "approve"
                    $subOrder->driver_accept_date_time = Carbon::now();
                    $subOrder->delivery_proof_img = $filename;
                    $subOrder->reason = $req->reason ?? null;
                    $subOrder->save();
                    $this->storeDriverTracker($subOrder->track_id, $trackStatus, $orderStatusMessages[$trackStatus]);

                    return response()->json(['message' => "Order $trackStatus successfully.", 'success' => true, 'data' => ''], 200);
                }
            }

            return response()->json(['message' => "Invalid status.", 'success' => false, 'data' => ''], 400);
        } catch (\Exception $th) {
            Log::error('driverChangeOrderStatus: ' . $th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // Helper function
    public function storeDriverTracker($track_id, $status, $trackerDesc)
    {
        OrderTrackDetails::create([
            'track_id' => $track_id,
            'status' => ucfirst($status),
            'track_desc' => ucfirst($status) . ' Date:' . Carbon::now() . ' -> ' . $trackerDesc,
        ]);
    }


    public function getDriverOrders(Request $req)
    {
        if (!$req->driver_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $query = SubOrders::query();

            $query->where('driver_id', $req->driver_id)
                ->with('Orders.user', 'OrderItems.foodItem', 'OrderTrack', 'chefs')
                ->whereHas('Orders', function ($subQuery) {
                    $subQuery->where('payment_status', 'paid');
                });

            if ($req->filter == true) {
                if ($req->from_date) {
                    $query->whereDate('created_at', '>=', $req->from_date);
                }

                if ($req->to_date) {
                    $query->whereDate('created_at', '<=', $req->to_date);
                }

                // Check if user_id is not empty
                if (!empty($req->user_id)) {
                    $query->whereHas('Orders.user', function ($subQuery) use ($req) {
                        $subQuery->where('user_id', $req->user_id);
                    });
                }
            }
            // fetch by chef name
            if ($req->chef_id) {
                $query->where('chef_id', $req->chef_id);
            }

            // Order in upcoming list
            if ($req->status) {
                $query->where('status', $req->status);
            }

            $query->orderBy('created_at', 'desc');
            $data = $query->get();

            // $data->transform(function ($subOrder) {
            //     if ($subOrder->chef_commission_taxes) {
            //         $subOrder->chef_commission_taxes = json_decode($subOrder->chef_commission_taxes, true);
            //     }
            //     return $subOrder;
            // });
            // $data->transform(function ($subOrder) {
            //     if ($subOrder->driver_commission_taxes) {
            //         $subOrder->driver_commission_taxes = json_decode($subOrder->driver_commission_taxes, true);
            //     }
            //     return $subOrder;
            // });
            // $data->transform(function ($subOrder) {
            //     if ($subOrder->sub_order_tax_detail) {
            //         $subOrder->sub_order_tax_detail = json_decode($subOrder->sub_order_tax_detail, true);
            //     }
            //     return $subOrder;
            // });
            return response()->json(['message' => '', "data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }
}
