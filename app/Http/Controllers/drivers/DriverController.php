<?php

namespace App\Http\Controllers\drivers;

use App\Http\Controllers\Controller;
use App\Mail\HomeshefDriverChangeEmailLink;
use App\Mail\HomeshefDriverEmailVerificationLink;
use App\Mail\HomeshefDriverEmailVerrifiedSuccessfully;
use App\Models\Admin;
use App\Models\Driver;
use App\Models\DriverContact;
use App\Models\DriverScheduleCall;
use App\Models\Pincode;
use App\Notifications\Driver\DriverContactUsNotification;
use App\Notifications\Driver\driverRegisterationNotification;
use App\Notifications\Driver\DriverScheduleCallNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Intervention\Image\Facades\Image;

class DriverController extends Controller
{
    function driverRegisteraion(Request $req)
    {
        $checkPinCode = Pincode::where(['pincode' => substr(str_replace(" ", "", strtoupper($req->postal_code)),0,3), 'status' => 1])->first();
        if (!$checkPinCode) {
            return response()->json(['message' => 'we are not offering our services in this region', 'ServiceNotAvailable' => true, 'success' => false], 500);
        }
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
            $driverExist = Driver::where('email', $req->email)->first();
            if ($driverExist) {
                return response()->json(["message" => 'Email already registered', "success" => false], 500);
            }
            $driverExist = Driver::where('mobileNo', str_replace("-", "", $req->mobileNo))->first();
            if ($driverExist) {
                return response()->json(["message" => 'Mobile No already registered', "success" => false], 500);
            }
            $driver = new Driver();
            $driver->first_name = $req->first_name;
            $driver->last_name = $req->last_name;
            $driver->email = $req->email;
            $driver->mobileNo = str_replace("-", "", $req->mobileNo);
            $driver->are_you_a = $req->are_you_a;
            $driver->password = Hash::make($req->password);
            $driver->full_address = $req->full_address;
            $driver->province = $req->province;
            $driver->city = $req->city;
            $driver->postal_code = strtoupper($req->postal_code);
            $driver->save();
            Mail::to(trim($req->email))->send(new HomeshefDriverEmailVerificationLink($driver));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new driverRegisterationNotification($driver));
            }
            return response()->json(["message" => 'Your registration has been successful', "success" => true, "driver_id" => $driver->id], 200);
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
                $driver = Driver::where('mobileNo', str_replace("-", "", $req->userName))->first();
            }
            // if (!$driver) {
            //     return response()->json(['message' => 'Driver not found', 'success' => false], 400);
            // }
            if ($driver) {
                $driver->makeVisible('password');
                if (Hash::check($req->password, $driver->password)) {
                    $driver->makeHidden('password');
                    return response()->json(['message' => 'Logged in successfully!', 'data' => $driver, 'success' => true], 200);
                } else {
                    return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
                }
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
            if (!$driver) {
                return response()->json(['message' => 'User not found', 'success' => false], 400);
            }
            Driver::where('email', $driver->email)->update(['password' => Hash::make($req->password)]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function updatePersonalDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "driver_id" => 'required',
            "first_name" => 'required',
            "last_name" => 'required',
            "full_address" => 'required',
            "province" => 'required',
            "city" => 'required',
            "postal_code" => 'required',
        ], [
            "driver_id.required" => "please fill driver_id",
            "first_name.required" => "please fill first_name",
            "last_name.required" => "please fill last_name",
            "full_address.required" => "please fill full_address",
            "province.required" => "please fill province",
            "city.required" => "please fill city",
            "postal_code.required" => "please fill postal_code",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = "driver/" . $req->driver_id . '/';
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            $update = [
                'first_name' => $req->first_name,
                'last_name' => $req->last_name,
                'full_address' => $req->full_address,
                'province' => $req->province,
                'city' => $req->city,
                'postal_code' => strtoupper($req->postal_code),
            ];
            if ($req->hasFile('profile_pic') || $req->hasFile('address_proof')) {
                $driver = Driver::find($req->driver_id);
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
            Driver::where('id', $req->driver_id)->update($update);
            return response()->json(['message' => 'Your information has been updated', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateDrivingLicence(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "driving_licence_no" => ['required', 'regex:/^[A-Z][0-9]{4} [0-9]{6} [0-9]{2}$/'],
        ], [
            "id.required" => "please fill password",
            "driving_licence_no.required" => "please fill driving licence no",
            "driving_licence_no.regex" => "Invalid driving licence number format. Please enter a valid format like 'A3567 678907 45'.",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = 'driver/' . $req->id;
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            if ($req->hasFile('driving_licence_proof')) {
                $driver = Driver::find($req->id);
                if (file_exists(str_replace(env('filePath'), '', $driver->driving_licence_proof))) {
                    unlink(str_replace(env('filePath'), '', $driver->driving_licence_proof));
                }
                $storedPath = $req->file('driving_licence_proof')->store($path, 'public');
                Driver::where('id', $req->id)->update(['driving_licence_proof' => asset('storage/' . $storedPath), 'status' => 0]);
            }
            Driver::where('id', $req->id)->update(['driving_licence_no' => $req->driving_licence_no]);
            return response()->json(['message' => 'Your license details has been updated', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function updateTaxationNo(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if (!File::exists("public/storage/driver/TaxInformation")) {
            File::makeDirectory("public/storage/driver/TaxInformation", $mode = 0777, true, true);
        }

        try {
            DB::beginTransaction();
            // Retrieve the chef's data
            $driver = Driver::find($req->id);

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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
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
    //         "id.required" => "please fill password",
    //         "full_address.required" => "please fill driving licence no",
    //         "province.required" => "please fill driving licence no",
    //         "city.required" => "please fill driving licence no",
    //         "postal_code.required" => "please fill driving licence no",
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
    //     } catch (\Throwable $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
    //     }
    // }

    function updateCriminialReport(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "criminal_report" => 'required',
        ], [
            "criminal_report.required" => "please fill criminal report",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = 'driver/' . $req->id;
            if ($req->hasFile('criminal_report')) {
                $driver = Driver::find($req->id);
                if (file_exists(str_replace(env('filePath'), '', $driver->criminal_report))) {
                    unlink(str_replace(env('filePath'), '', $driver->criminal_report));
                }
                $storedPath = $req->file('criminal_report')->store($path, 'public');
                Driver::where('id', $req->id)->update(['criminal_report' => asset('storage/' . $storedPath), 'status' => 0]);
            }
            return response()->json(['message' => 'updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function updateDriverBankDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "bank_name" => 'required',
            "transit_number" => 'required',
            "account_number" => 'required',
            "institution_number" => 'required',
        ], [
            "id.required" => "please fill id",
            "bank_name.required" => "please fill bank name",
            "transit_number.required" => "please fill driving transit number",
            "account_number.required" => "please fill driving account number",
            "institution_number.required" => "please fill driving institution number",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $update = [
                'bank_name' => $req->bank_name,
                'transit_number' => $req->transit_number,
                'account_number' => $req->account_number,
                'institution_number' => $req->institution_number,
            ];
            Driver::where('id', $req->id)->update($update);
            return response()->json(["message" => 'updated successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to login again !', 'success' => false], 500);
        }
    }

    function driverScheduleAnCall(Request $req)
    {
        if (!$req->driver_id || !$req->date || !$req->slot) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false], 400);
        }
        try {
            $slotNotAvailable = DriverScheduleCall::where(['date' => $req->date, 'slot' => $req->slot])->first();
            if ($slotNotAvailable) {
                return response()->json(['message' => 'Slot not available select another slot', 'success' => false], 500);
            }

            $SameChefSameSlot = DriverScheduleCall::where(['driver_id' => $req->driver_id, 'date' => $req->date, 'slot' => $req->slot])->first();
            if ($SameChefSameSlot) {
                return response()->json(['message' => 'Already booked on same slot', 'success' => false], 500);
            }

            $scheduleNewCall = new DriverScheduleCall();
            $scheduleNewCall->driver_id = $req->driver_id;
            $scheduleNewCall->date = $req->date;
            $scheduleNewCall->slot = $req->slot;
            $scheduleNewCall->save();

            $ScheduleCall = DriverScheduleCall::orderBy('created_at', 'desc')->where('driver_id', $req->driver_id)->with('driver')->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverScheduleCallNotification($ScheduleCall));
            }

            return response()->json(["message" => 'Call has been scheduled successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function AddDriverContactData(Request $req)
    {
        if (!$req->driver_id) {
            return response()->json(["message" => "please fill all the required fields ", "success" => false], 400);
        }

        try {
            $contact = new DriverContact();
            $contact->driver_id = $req->driver_id;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();
            $contactUs = DriverContact::orderBy('created_at', 'desc')->where('driver_id', $req->driver_id)->with('driver')->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new DriverContactUsNotification($contactUs));
            }
            return response()->json(['message' => 'Submitted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getMyDetails(Request $req)
    {
        if (!$req->driver_id) {
            return response()->json(["message" => "please fill all the required fields ", "success" => false], 400);
        }
        try {
            $driver = Driver::find($req->driver_id);
            return response()->json(['data' => $driver, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function driverUpdateEmail(Request $req)
    {
        if (!$req->driver_id || !$req->email) {
            return response()->json(["message" => "please fill all the required fields ", "success" => false], 400);
        }
        try {
            Driver::where('id', $req->driver_id)->update(['email' => $req->email, 'status' => 0]);
            $driver = Driver::find($req->driver_id);

            Mail::to(trim($req->email))->send(new HomeshefDriverChangeEmailLink($driver));
            return response()->json(['message' => 'Your email has been updated', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function VerifyDriverEmail(Request $req)
    {
        if (!$req->id) {
            return response()->json(["message" => 'please fill all the details', "success" => false], 400);
        }
        try {
            $checkVerification = Driver::find($req->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                Driver::where('id', $req->id)->update(['email_verified_at' => Carbon::now(), 'is_email_verified' => 1]);
                $driverDetails = Driver::find($req->id);
                Mail::to(trim($driverDetails->email))->send(new HomeshefDriverEmailVerrifiedSuccessfully($driverDetails));
                return response()->json(['message' => 'Email has been verified successfully', 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }
}
