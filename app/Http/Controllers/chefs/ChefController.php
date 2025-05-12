<?php

namespace App\Http\Controllers\chefs;

use App\Helpers\AwsHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Mail\FoodCertificateMail;
use App\Mail\FoodLicenseEmail;
use App\Mail\HomeshefChefChangeEmailVerificationLink;
use App\Mail\HomeshefChefEmailVerification;
use App\Mail\HomeshefChefEmailVerifiedSuccessfully;
use App\Mail\HomeshefChefStatusChangeMail;
use App\Mail\HomeshefFoodItemStatusChange;
use App\Models\Admin;
use App\Models\Adminsetting;
use App\Models\Allergy;
use App\Models\Chef;
use App\Models\ChefAlternativeContact;
use App\Models\ChefDocument;
use App\Models\ChefProfileReviewByAdmin;
use App\Models\ChefReview;
use App\Models\ChefReviewDeleteRequest;
use App\Models\ChefSuggestion;
use App\Models\DocumentItemList;
use App\Models\FoodItem;
use App\Models\FoodLicense;
use App\Models\RequestForUpdateDetails;
use App\Models\RequestForUserBlacklistByChef;
use App\Models\ScheduleCall;
use App\Models\ShefRegisterationRequest;
use App\Models\State;
use App\Models\User;
use App\Models\Contact;
use App\Models\Dietary;
use App\Models\Ingredient;
use App\Models\OrderStatus;
use App\Models\Kitchentype;
use App\Models\OrderTrackDetails;
use App\Notifications\admin\ChefRegisterationRequest;
use App\Notifications\admin\RequestQueryNotification;
use App\Notifications\Chef\ChefContactUsNotification;
use App\Notifications\Chef\ChefFoodItemNotification;
use App\Notifications\Chef\ChefFoodLicense;
use App\Notifications\Chef\ChefScheduleCallNotification;
use App\Notifications\Chef\ChefStatusUpdateNotification;
use App\Notifications\Chef\chefSuggestionNotifications;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Pincode;
use App\Models\SubOrders;
use App\Notifications\admin\requestForBlacklistUser;
use App\Notifications\admin\requestForChefReviewDelete;
use App\Notifications\Chef\ChefRegisterationNotification;
use App\Notifications\Chef\foodItemstatusChangeMail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use App\Notifications\Chef\ChefSendReviewToAdmin;
use App\Notifications\Chef\FoodCertificateNotification;
use App\Mail\HomeshefUserEmailVerificationMail;
use Aws\S3\S3Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Tymon\JWTAuth\Facades\JWTAuth;

class ChefController extends Controller
{

    function ChefRegisteration(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'email' => 'required|email|unique:chefs,email',
            'mobile' => 'required|unique:chefs,mobile',
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'date_of_birth' => 'required|date',
            'postal_code' => 'required|string|max:10',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 400);
        }
        try {
            DB::beginTransaction();
            $checkPinCode = Pincode::where([
                'pincode' => substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3),
                'status' => 1
            ])->first();

            if (!$checkPinCode) {
                return response()->json([
                    'message' => 'We are currently not offering our services in this region yet.',
                    'ServiceNotAvailable' => true,
                    'success' => false
                ], 200);
            }

            // Check if the email or mobile is already used in the chefs table
            $chefExist = Chef::where("email", $req->email)->first();
            if ($chefExist) {
                return response()->json(['message' => "This email is already register Please use another email!", "success" => false], 400);
            }
            $chefExist = Chef::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefExist) {
                return response()->json(['message' => "This mobile no is already register Please use another mobileno!", "success" => false], 400);
            }

            // Check if the email or mobile is already used in the users table
            $userExist = User::where("email", $req->email)->orWhere('mobile', str_replace("-", "", $req->mobile))->first();
            $createUser = !$userExist; // Flag to indicate if we should create a new user

            $chef = new Chef();
            $chef->firstName = ucfirst($req->firstName);
            $chef->lastName = ucfirst($req->lastName);
            $chef->date_of_birth = $req->date_of_birth;
            $chef->postal_code = str_replace(" ", "", strtoupper($req->postal_code));
            $chef->mobile = str_replace("-", "", $req->mobile);
            $chef->is_mobile_verified = 0;
            $chef->email = $req->email;
            $chef->password = Hash::make($req->password);
            if ($req->newToCanada == 1) {
                $chef->new_to_canada = $req->newToCanada;
            }
            $chef->save();
            $chefDetail = Chef::find($chef->id);

            if ($createUser) {
                $userDetail = new User();
                $userDetail->firstName = $chef->firstName;
                $userDetail->lastName = $chef->lastName;
                $userDetail->mobile = $chef->mobile;
                $userDetail->email = $chef->email;
                $userDetail->password = $chef->password; // Same password as chef
                $userDetail->save();

                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($userDetail->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }

            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($req->email))->send(new HomeshefChefEmailVerification($chefDetail));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }

            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new ChefRegisterationNotification($chefDetail));
            }

            DB::commit();
            return response()->json([
                'message' => $createUser ? 'You have successfully registered as both a chef and a customer!' : 'You have successfully registered as a chef!',
                "data" => $chefDetail,
                'success' => true
            ], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function ChefLogin(Request $req)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        $validate = Validator::make($req->all(), $rules);
        if ($validate->fails()) {
            return response()->json(['message' => 'Please fill all the fields', 'success' => false], 400);
        }
        $chefDetail = Chef::where("email", $req->email)->first();

        if ($chefDetail) {
            $chefDetail->makeVisible('password');
            if (Hash::check($req->password, $chefDetail['password'])) {
                $chefDetail->makeHidden('password');
                //Generate JWT Toekn
                if (!$token = auth('chef')->attempt(['email' => $req->email, 'password' => $req->password])) {
                    return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
                }
                // return Chef::createToken($token);
                return response()->json(['message' => 'You are logged in now !', 'data' => $chefDetail, 'token' => Chef::createToken($token), 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 500);
        }
    }

    public function chefProfile()
    {
        // Retrieve the authenticated driver
        $chef = auth()->guard('chef')->user();
        // Check if the chef is authenticated
        if (!$chef) {
            return response()->json(['message' => 'Driver not found', 'success' => false], 404);
        }
        // Return the driver's profile
        return response()->json(['success' => true, 'data' => $chef], 200);
    }

    public function chefLogout()
    {
        auth()->guard('chef')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function chefRefreshToken()
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
            return response()->json([
                'success' => false,
                'message' => 'Failed to refresh token, please try again'
            ], 500);
        }
    }

    function EditPersonalInfo(Request $req)
    {

        $validator = Validator::make($req->all(), [
            "profile_pic" => 'nullable',
            // "chef_id" => 'required',
            "firstName" => 'required',
            "lastName" => 'required',
            "type" => 'required',
            "sub_type" => "required",
            "address_line1" => "required",
            "postal_code" => 'required',
            "latitude" => "required",
            "longitude" => "required",
            "city" => "required",
            "state" => "required"
        ], [
            // "chef_id.required" => "Please mention chef_id",
            "firstName.required" => "Please fill firstname",
            "lastName.required" => "Please fill lastname",
            "type.required" => "Please select type",
            "sub_type.required" => "Please select sub-type",
            "address_line1.required" => "Please fill addressLine1",
            "postal_code" => "Please fill postal code",
            "latitude" => "Please fill latitude",
            "longitude" => "Please fill longitude",
            "city" => "Please fill city",
            "state" => "Please fill state"
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $chefInfo = auth()->guard('chef')->user();
            $update = [
                "firstName" => ucfirst($req->firstName),
                "lastName" => ucfirst($req->lastName),
                "type" => ucfirst($req->type),
                "sub_type" => ucfirst($req->sub_type),
                "address_line1" => htmlspecialchars(ucfirst($req->address_line1)),
                "postal_code" => strtoupper($req->postal_code),
                "latitude" => isset($req->latitude) ? $req->latitude : '',
                "longitude" => isset($req->longitude) ? $req->longitude : '',
                "city" => isset($req->city) ? $req->city : '',
                "state" => isset($req->state) ? $req->state : '',
                'status' => 0
            ];

            if ($req->hasFile('profile_pic')) {
                try {
                    $file = $req->file('profile_pic');
                    $fileName = 'profile_chef/' . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                    $url = $result['ObjectURL'];

                    // Log the result
                    Log::info('S3 Upload Success', ['url' => $url]);

                    // Save URL to database
                    $update['profile_pic'] = $url;

                } catch (\Exception $e) {
                    Log::error('S3 Upload Failed', ['error' => $e->getMessage()]);
                    return response()->json([
                        "error" => $e->getMessage()
                    ], 500);
                }
            }


            Chef::where('id', $chefInfo->id)->update($update);
            return response()->json(["message" => "Profile updated successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = Chef::whereId($req->chef_id)->first();
            if ($data->state != "null") {

                $documents = ChefDocument::where('chef_id', $req->chef_id)->get();

                $stateDetail = State::where('name', $data->state)->first();
                if ($stateDetail != null) {
                    $DocList = DocumentItemList::with('documentItemFields')->where(["state_id" => $stateDetail->id, 'status' => 1])->get();

                    foreach ($DocList as &$list) {
                        // Use arrow operator to access the relationship
                        $fields = $list->documentItemFields;
                        foreach ($fields as &$field) {
                            foreach ($documents as $docs) {
                                if ($docs->document_field_id == $field->id) {
                                    $field->value = $docs->field_value;
                                }
                            }
                        }
                    }
                    $data['chef_documents'] = $DocList;
                }
            }
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    function updateChefDetailsStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "Please fill chef id",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Chef::where('id', $req->id)->update(['status' => $req->status]);
            $chefDetail = Chef::find($req->id);
            $chefDetail->notify(new ChefStatusUpdateNotification($chefDetail));
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($chefDetail->email))->send(new HomeshefChefStatusChangeMail($chefDetail));
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

    function updateChefPrimaryEmail(Request $req)
    {
        if (!$req->new_email) {
            return response()->json(["message" => "Please provide new email", "success" => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $chefDetails = Chef::find($chef->id);
            if ($chefDetails->email == $req->new_email) {
                return response()->json(['message' => 'Trying to use an existing primary email. Please use another email.', "success" => false], 500);
            }

            $isNewEmailAlreadyRegistered = Chef::where('email', $req->new_email)->first();
            if (!$isNewEmailAlreadyRegistered) {
                $chefDetails->email = trim($req->new_email);
                $chefDetails->is_email_verified = 0;
                $chefDetails->email_verified_at = null;
                $chefDetails->status = 0;
                $chefDetails->save();
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($req->new_email))->send(new HomeshefChefChangeEmailVerificationLink($chefDetails));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
                return response()->json(['message' => "Updated sucessfully", "success" => true], 200);
            } else {
                return response()->json(["error" => "This email is already registerd with Homeshef", "success" => false], 500);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateSocialMediaLinks(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $chef = Chef::find($req->chef_id);
            if ($req->twitter_link) {
                $chef->twitter_link = $req->twitter_link;
            }
            if ($req->facebook_link) {
                $chef->facebook_link = $req->facebook_link;
            }
            if ($req->tiktok_link) {
                $chef->tiktok_link = $req->tiktok_link;
            }
            $chef->status = 0;
            $chef->save();
            return response()->json(['message' => 'Updated successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    function updateBankDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "chef_id" => 'required',
            "bank_name" => 'required',
            "account_number" => 'required',
            "transit_number" => 'required',
            "institution_number" => 'required',
        ], [
            // "chef_id.required" => "Please mention chef_id",
            "bank_name.required" => "Please fill bank_name",
            "account_number.required" => "Please fill account_number",
            "transit_number.required" => "Please select transit_number",
            "institution_number.required" => "Please select institution_number",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $chef = Chef::find($chef->id);
            $chef->bank_name = $req->bank_name;
            $chef->account_number = $req->account_number;
            $chef->transit_number = $req->transit_number;
            $chef->institution_number = $req->institution_number;
            $chef->status = 0;
            $chef->save();
            return response()->json(['message' => "Updated successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateDocuments(Request $req)
    {
        try {
            $chef = auth()->guard('chef')->user();
            DB::beginTransaction();
            $path = 'chef/' . $chef->id;

            $chef = Chef::find($chef->id);

            // store address proof
            if (isset($req->address_proof) && $req->hasFile('address_proof_path')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->address_proof_path))) {
                    unlink(str_replace(env('filePath'), '', $chef->address_proof_path));
                }
                $file = $req->file('address_proof_path');
                $fileName = 'chef/'. $chef->id.'/documents' . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                    $url = $result['ObjectURL'];

                    // Log the result
                    Log::info('S3 Upload Success', ['url' => $url]);

                    // Save URL to database

                $storedPath = $req->file('address_proof_path')->store($path, 'public');
                Chef::where("id", $chef->id)->update(["address_proof_path" => $url, "address_proof" => $req->address_proof, 'status' => 0]);
            }

            // store ID proof 1
            if (isset($req->id_proof_1) && $req->hasFile('id_proof_path1')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->id_proof_path1))) {
                    unlink(str_replace(env('filePath'), '', $chef->id_proof_path1));
                }
                $file = $req->file('id_proof_path1');
                $fileName = 'chef/'. $chef->id.'/documents' . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                    $url = $result['ObjectURL'];

                    // Log the result
                Log::info('S3 Upload Success', ['url' => $url]);
                $storedPath = $req->file('id_proof_path1')->store($path, 'public');
                Chef::where("id", $chef->id)->update(["id_proof_1" => $req->id_proof_1, "id_proof_path1" => $url, 'status' => 0]);
            }

            // store ID proof 2
            if (isset($req->id_proof_2) && $req->hasFile('id_proof_path2')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->id_proof_path1))) {
                    unlink(str_replace(env('filePath'), '', $chef->id_proof_path1));
                }

                $file = $req->file('id_proof_path1');
                $fileName = 'chef/'. $chef->id.'/documents' . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                    $url = $result['ObjectURL'];

                    // Log the result
                Log::info('S3 Upload Success', ['url' => $url]);

                $storedPath = $req->file('id_proof_path2')->store($path, 'public');
                Chef::where("id", $chef->id)->update(["id_proof_2" => $req->id_proof_2, "id_proof_path2" =>$url, 'status' => 0]);
            }

            // Additional fields which has values in string
            if (isset($req->typeTextData)) {
                $textTypeData = $req->typeTextData;
                foreach ($textTypeData as $value) {
                    $data = json_decode($value);
                    if (isset($data->value) && $data->value != "") {
                        ChefDocument::updateOrCreate(
                            [
                                "chef_id" => $chef->id,
                                "document_field_id" => $data->id
                            ],
                            [
                                "field_value" => $data->value
                            ]
                        );
                        Chef::where("id", $chef->id)->update(['status' => 0]);
                    }
                }
            }

            // Additional fields which has values in files
            if (isset($req->files) && isset($req->id)) {
                $fieldsArray = $req->input('id');
                $filesArray = $req->file('files');
                foreach ($fieldsArray as $index => $value) {
                    if (isset($filesArray[$index])) {

                        $storedPath = $filesArray[$index]->store($path, 'public');

                        ChefDocument::updateOrCreate(
                            [
                                "chef_id" => $chef->id,
                                "document_field_id" => $value
                            ],
                            [
                                "field_value" => asset('storage/' . $storedPath)
                            ]
                        );
                        Chef::where("id", $chef->id)->update(['status' => 0]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => "Updated successfully", 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateKitchen(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                'chef_banner_image' => 'nullable|image|mimes:jpeg,jpg,png|max:250', // Adjust max size in KB
                'chef_card_image' => 'nullable|image|mimes:jpeg,jpg,png|max:250',
                'about_kitchen' => 'nullable|between:300,1000',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        // if (!$req->chef_id) {
        //     return response()->json(['message' => "Please fill all the required fields", "success" => false], 400);
        // }
        try {
            $chef = auth()->guard('chef')->user();
            DB::beginTransaction();
            $path = 'chef/' . $chef->id;
            $chef = Chef::find($chef->id);

            if ($req->hasFile('chef_banner_image')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->chef_banner_image))) {
                    unlink(str_replace(env('filePath'), '', $chef->chef_banner_image));
                }
                $file = $req->file('chef_banner_image');
                $fileName = 'chef/kitchen' . $chef->id . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                $url = $result['ObjectURL'];

                // $storedPath = $req->file('chef_banner_image')->store($path, 'public');
                Chef::where("id", $chef->id)->update(["chef_banner_image" => $url]);
            }

            if ($req->hasFile('chef_card_image')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->chef_card_image))) {
                    unlink(str_replace(env('filePath'), '', $chef->chef_card_image));
                }
                $file = $req->file('chef_card_image');
                $fileName = 'chef/kitchen' . $chef->id . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                $url = $result['ObjectURL'];

                $storedPath = $req->file('chef_card_image')->store($path, 'public');
                Chef::where("id", $chef->id)->update(["chef_card_image" => $url]);
            }

            Log::info($req);
            $update = [
                "kitchen_types" => $req->kitchen_types,
                "about_kitchen" => $req->about_kitchen,
                "kitchen_name" => $req->kitchen_name,
                'status' => 0
            ];

            Chef::where('id', $chef->id)->update($update);
            DB::commit();
            return response()->json(["message" => "Updated successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateSpecialBenifits(Request $req)
    {
        // if (!$req->chef_id) {
        //     return response()->json(['message' => "Please fill all the required fields", "success" => false], 400);
        // }
        try {
            $chef = auth()->guard('chef')->user();
            $path = 'chef/' . $chef->id;
            $chef = Chef::find($chef->id);
            if ($req->hasFile('are_you_a_file_path')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->are_you_a_file_path))) {
                    unlink(str_replace(env('filePath'), '', $chef->are_you_a_file_path));
                }

                $file = $req->file('are_you_a_file_path');
                $fileName = 'chef/special_benifit' . $chef->id . time() . '_' . $file->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);
                    // Get the public URL of the uploaded file
                    $url = $result['ObjectURL'];
                Chef::where("id", $chef->id)->update(["are_you_a_file_path" => $url, "are_you_a" => $req->are_you_a, 'status' => 0]);
                return response()->json(["message" => "Updated successfully", "success" => true], 200);
            } else {
                return response()->json(["message" => "Please upload proof of " . $req->are_you_a, "success" => false], 500);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function AddContactData(Request $req)
    {
        // if (!$req->chef_id) {
        //     return response()->json(["message" => "Please fill all the required fields ", "success" => false], 400);
        // }
        try {
            $chef = auth()->guard('chef')->user();
            $contact = new contact();
            $contact->chef_id = $chef->id;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();
            $contactUs = contact::orderBy('created_at', 'desc')->where('chef_id', $chef->id)->with('chef')->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new ChefContactUsNotification($contactUs));
            }
            return response()->json(['message' => 'Form submitted !', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function chefScheduleAnCall(Request $req)
    {
        if (!$req->chef_id || !$req->date || !$req->slot) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false], 400);
        }
        try {
            $slotNotAvailable = ScheduleCall::where(['date' => $req->date, 'slot' => $req->slot])->first();
            if ($slotNotAvailable) {
                return response()->json(['message' => 'Slot not available select another slot', 'success' => false], 500);
            }
            $SameChefSameSlot = ScheduleCall::where(['chef_id' => $req->chef_id, 'date' => $req->date, 'slot' => $req->slot])->first();
            if ($SameChefSameSlot) {
                return response()->json(['message' => 'Already booked on same slot', 'success' => false], 500);
            }
            $scheduleNewCall = new ScheduleCall();
            $scheduleNewCall->chef_id = $req->chef_id;
            $scheduleNewCall->date = $req->date;
            $scheduleNewCall->slot = $req->slot;
            $scheduleNewCall->save();

            $ScheduleCall = ScheduleCall::orderBy('created_at', 'desc')->where('chef_id', $req->chef_id)->with('chef')->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new ChefScheduleCallNotification($ScheduleCall));
            }
            return response()->json(["message" => 'Your requested call  has been scheduled.', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function deleteChefSchedule(Request $req)
    {
        try {
            $chefSchedule = ScheduleCall::findOrFail($req->id);
            if (!$chefSchedule) {
                return response()->json(['success' => false, 'message' => 'Schedule call not available', 'data' => ''], 400);
            }
            $chefSchedule->delete();

            return response()->json(['success' => true, 'message' => 'Schedule call deleted successfully!', 'data' => $chefSchedule], 200);
        } catch (\Exception $e) {
            Log::info($e);
            return redirect()->back()->with('error', 'Failed to delete the Benefits. Please try again.');
        }
    }


    function chefAddNewOrUpdateFoodItem(Request $req)
    {
        try {
            $chef = auth()->guard('chef')->user();
            if ($req->food_id) {
                $foodData = FoodItem::find($req->food_id);
                $foodData->fill($req->all());
                $foodData['foodAvailibiltyOnWeekdays'] = json_decode($req->foodAvailibiltyOnWeekdays);
                $foodData['geographicalCuisine'] = json_decode($req->geographicalCuisine);
                if ($req->otherCuisine) {
                    $foodData['otherCuisine'] = json_decode($req->otherCuisine);
                }
                $foodData['ingredients'] = json_decode($req->ingredients);
                if ($req->otherIngredients) {
                    $foodData['otherIngredients'] = json_decode($req->otherIngredients);
                }
                if ($req->allergies) {
                    $foodData['allergies'] = json_decode($req->allergies);
                }
                if ($req->dietary) {
                    $foodData['dietary'] = json_decode($req->dietary);
                }
                $OGfilePath = "";
                $filename_thumb = "";
                if ($req->hasFile('foodImage')) {
                    $directoryPath = 'foodItem/';
                    $directoryPathThumbnail = 'foodItem/thumbnail/';

                    // Generate unique filename
                    $name_gen = hexdec(uniqid()) . '.' . $req->file('foodImage')->getClientOriginalExtension();

                    // Process image with Intervention/Image
                    $image = Image::make($req->file('foodImage'))->fit(800, 800);
                    $thumbImage = Image::make($req->file('foodImage'))->fit(200, 200);

                    // Save images temporarily
                    $OGfilePath = storage_path('app/public/' . $name_gen); // Full path for original image
                    $filename_thumb = storage_path('app/public/thumb_' . $name_gen); // Full path for thumbnail

                    $image->save($OGfilePath);
                    $thumbImage->save($filename_thumb);

                    // Upload to S3
                    $s3 = AwsHelper::cred();
                    $result1 = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $directoryPath . $name_gen,
                        'Body'   =>  fopen($OGfilePath, 'r'),
                        'ContentType' => $req->file('foodImage')->getMimeType(),
                    ]);
                    $url1 = $result1['ObjectURL'];

                    $result = $s3->putObject([
                        'Bucket'      => env('AWS_BUCKET'),
                        'Key'         => $directoryPathThumbnail . $name_gen,
                        'Body'        => fopen($filename_thumb, 'r'), // Now the file exists
                        'ContentType' => $req->file('foodImage')->getMimeType(),
                    ]);
                    $url = $result['ObjectURL'];

                    // Append new image URLs instead of replacing old ones
                    $foodData->dishImage = $url1;
                    $foodData->dishImageThumbnail =  $url;

                    // Delete temporary files after upload to free space
                    unlink($OGfilePath);
                    unlink($filename_thumb);
                }

                $foodData->save();
                $chefDetail = Chef::find($chef->id);
                $chefDetail['flag'] = 2;
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new ChefFoodItemNotification($chefDetail));
                }
                return response()->json(['message' => 'Item updated successfully', 'success' => true], 200);
            } else {
                $validator = Validator::make(
                    $req->all(),
                    [
                        'dish_name' => 'required',
                        'description' => 'required',
                        'foodImage' => 'required|image|mimes:jpeg,png,jpg|max:1024|dimensions:width=350,height=350',
                        'regularDishAvailabilty' => 'required',
                        'from' => 'nullable',
                        'to' => 'nullable',
                        'foodAvailibiltyOnWeekdays' => 'required',
                        'orderLimit' => 'required|numeric',
                        'foodTypeId' => 'required',
                        'spicyLevel' => 'required',
                        'heating_instruction_id' => 'required',
                        'heating_instruction_description' => 'required',
                        'package' => 'required',
                        'size' => 'required',
                        'expiresIn' => 'required',
                        'serving_unit' => 'required',
                        'serving_person' => 'required',
                        'price' => 'required',
                        'comments' => 'nullable|string|max:300',
                    ],
                    [
                        'dish_name.required' => 'Please mention dish name',
                        'description.required' => 'Please add dish discription',
                        'foodImage.required' => 'Please add dish image',
                        'image.image' => 'The uploaded file is not a valid image.',
                        'image.mime' => 'The uploaded image must be a JPEG, BMP, or PNG file.',
                        'image.max' => 'The uploaded image size must be under 100 KB.',
                        'image.dimensions' => 'The uploaded image must be at least 350x350 pixels.',
                        'foodImage.image' => 'Please select image file only',
                        'regularDishAvailabilty.required' => 'Please mention regularity of the dish',
                        'foodAvailibiltyOnWeekdays.required' => 'Please mention weekdays availablity of the food',
                        'orderLimit.required' => 'Please mention order limit',
                        'orderLimit.numeric' => 'order limit must be in number only',
                        'foodTypeId.required' => 'Please select food type',
                        'spicyLevel.required' => 'Please select spicy level of the food',
                        'heating_instruction_id.required' => 'Please select heating instruction option',
                        'heating_instruction_description.required' => 'Please enter food heading instruction',
                        'package.required' => 'Please select package type',
                        'size.required' => 'Please enter package size',
                        'expiresIn.required' => 'Please mention the expirey period of the food',
                        'serving_unit.required' => 'Please mention serving unit',
                        'serving_person.required' => 'Please mention the food sufficency',
                        'price.required' => 'Please mention the price of the food',
                        'comments.max' => 'The comment must be less than 300 characters.',
                    ]
                );

                if ($validator->fails()) {
                    return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
                }

                $OGfilePath = "";
                $filename_thumb = "";
                if ($req->hasFile('foodImage')) {
                    $directoryPath = 'foodItem/';
                    $directoryPathThumbnail = 'foodItem/thumbnail/';

                    // Generate unique filename
                    $name_gen = hexdec(uniqid()) . '.' . $req->file('foodImage')->getClientOriginalExtension();

                    // Process image with Intervention/Image
                    $image = Image::make($req->file('foodImage'))->fit(800, 800);
                    $thumbImage = Image::make($req->file('foodImage'))->fit(200, 200);

                    // Save images temporarily
                    $OGfilePath = storage_path('app/public/' . $name_gen); // Full path for original image
                    $filename_thumb = storage_path('app/public/thumb_' . $name_gen); // Full path for thumbnail

                    $image->save($OGfilePath);
                    $thumbImage->save($filename_thumb);

                    // Upload to S3
                    $s3 = AwsHelper::cred();
                    $result1 = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $directoryPath . $name_gen,
                        'Body'   =>  fopen($OGfilePath, 'r'),
                        'ContentType' => $req->file('foodImage')->getMimeType(),
                    ]);
                    $url1 = $result1['ObjectURL'];

                    $result = $s3->putObject([
                        'Bucket'      => env('AWS_BUCKET'),
                        'Key'         => $directoryPathThumbnail . $name_gen,
                        'Body'        => fopen($filename_thumb, 'r'), // Now the file exists
                        'ContentType' => $req->file('foodImage')->getMimeType(),
                    ]);
                    $url = $result['ObjectURL'];

                    // Delete temporary files after upload to free space
                    unlink($OGfilePath);
                    unlink($filename_thumb);
                }

                DB::beginTransaction();
                $foodItem = new FoodItem();
                $foodItem->chef_id = $chef->id;
                $foodItem->dish_name = $req->dish_name;
                $foodItem->description = $req->description;
                $foodItem->dishImage =$url1;
                $foodItem->dishImageThumbnail = $url;;
                $foodItem->regularDishAvailabilty = $req->regularDishAvailabilty;
                $foodItem->from = $req->from;
                $foodItem->to = $req->to;
                $foodItem->foodAvailibiltyOnWeekdays = json_decode($req->foodAvailibiltyOnWeekdays);
                $foodItem->orderLimit = $req->orderLimit;
                $foodItem->foodTypeId = $req->foodTypeId;
                $foodItem->spicyLevel = $req->spicyLevel;
                $foodItem->geographicalCuisine = json_decode($req->geographicalCuisine);
                $foodItem->otherCuisine = json_decode($req->otherCuisine);
                $foodItem->ingredients = json_decode($req->ingredients);
                $foodItem->otherIngredients = json_decode($req->otherIngredients);
                $foodItem->allergies = json_decode($req->allergies);
                $foodItem->dietary = json_decode($req->dietary);
                $foodItem->heating_instruction_id = $req->heating_instruction_id;
                $foodItem->heating_instruction_description = $req->heating_instruction_description;
                $foodItem->packageInstructions = $req->packageInstructions;
                $foodItem->package = $req->package;
                $foodItem->size = $req->size;
                $foodItem->expiresIn = $req->expiresIn;
                $foodItem->serving_unit = $req->serving_unit;
                $foodItem->serving_person = $req->serving_person;
                $foodItem->price = $req->price;
                $foodItem->comments = isset($req->comments) ? $req->comments : '';
                $foodItem->save();
                DB::commit();
                $chefDetail = Chef::find($chef->id);
                $chefDetail['flag'] = 1;
                $chefDetail['food_name'] = $req->dish_name;
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new ChefFoodItemNotification($chefDetail));
                }
                return response()->json(['message' => "Your new dish has been added successfully.", "success" => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function getMyFoodItems(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false], 400);
        }
        try {
            $where = ["chef_id" => $req->chef_id];

            if ($req->foodType) {
                $where['foodTypeId'] = $req->foodType;
            }
            if ($req->approved) {
                $where['approved_status'] = $req->approved;
            } else {
                $where['approved_status'] = 'approved';
            }

            // Start query and ensure it filters by chef_id first
            $query = FoodItem::with('category', 'heatingInstruction:id,title,description')
                ->where($where);

            // Apply JSON_CONTAINS condition only if 'day' is provided
            if ($req->day) {
                $query->whereRaw("JSON_CONTAINS(foodAvailibiltyOnWeekdays, '\"$req->day\"')");
            }

            $data = $query->get();

            $chefData = Chef::where('id', $req->chef_id)->first(['chefAvailibilityWeek']);

            foreach ($data as &$value) {
                // Allergies
                if ($value['allergies']) {
                    $AllergyArr = array_map(function ($val) {
                        return Allergy::select('id', 'image', 'allergy_name')->find($val);
                    }, $value['allergies']);
                    $value['allergies'] = $AllergyArr;
                }

                // Dietary
                if ($value['dietary']) {
                    $DietaryArr = array_map(function ($val) {
                        return Dietary::select('id', 'image', 'diet_name')->find($val);
                    }, $value['dietary']);
                    $value['dietary'] = $DietaryArr;
                }

                // Ingredients
                if ($value['ingredients']) {
                    $IngredientArr = array_map(function ($val) {
                        return Ingredient::select('ing_name')->find($val);
                    }, $value['ingredients']);
                    $value['ingredients'] = $IngredientArr;
                }

                // Geographical cuisines
                if ($value['geographicalCuisine']) {
                    $GeographicalCuisineArr = array_map(function ($val) {
                        return Kitchentype::select('kitchentype')->find($val);
                    }, $value['geographicalCuisine']);
                    $value['geographicalCuisine'] = $GeographicalCuisineArr;
                }

                // Determine availability
                $value['available'] = false;
                if ($chefData && $chefData->chefAvailibilityWeek && in_array($req->todaysWeekDay, $chefData->chefAvailibilityWeek)) {
                    $value['available'] = in_array($req->todaysWeekDay, $value->foodAvailibiltyOnWeekdays);
                }
            }

            $chefData = Chef::select('rating')->where('id', $req->chef_id)->first();

            return response()->json([
                "data" => $data,
                "rating" => $chefData->rating ?? null,
                "success" => true
            ], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function getFoodItemsForCustomer(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false], 400);
        }
        try {
            $where = ["chef_id" => $req->chef_id];
            if ($req->foodType) {
                $where['foodTypeId'] = $req->foodType;
            }
            if ($req->approved) {
                $where['approved_status'] = $req->approved;
            } else {
                $where['approved_status'] = 'approved';
            }
            $query = FoodItem::with('category', 'heatingInstruction:id,title,description')->where($where);
            if ($req->day) {
                $query->whereRaw("JSON_CONTAINS(foodAvailibiltyOnWeekdays,'\"$req->day\"')");
            }

            $data = $query->get(['*']);
            $chefData = Chef::where('id', $req->chef_id)->first(['chefAvailibilityWeek']);
            foreach ($data as &$value) {
                // Allergy
                if ($value['allergies']) {
                    $AllergyArr = $value['allergies'];
                    foreach ($AllergyArr as &$val) {
                        $val = Allergy::select('id', 'image', 'allergy_name')->find($val);
                    }
                    $value['allergies'] = $AllergyArr;
                }

                // dietary
                if ($value['dietary']) {
                    $DieatryArr = $value['dietary'];
                    foreach ($DieatryArr as &$val) {
                        $val = Dietary::select('id', 'image', 'diet_name')->find($val);
                    }
                    $value['dietary'] = $DieatryArr;
                }

                // Ingredient
                if ($value['ingredients']) {
                    $IngredientArr = $value['ingredients'];
                    foreach ($IngredientArr as &$val) {
                        $val = Ingredient::select('ing_name')->find($val);
                    }
                    $value['ingredients'] = $IngredientArr;
                }

                // geographical cuisines
                if ($value['geographicalCuisine']) {
                    $geographicalCuisine = $value['geographicalCuisine'];
                    foreach ($geographicalCuisine as &$val) {
                        $val = Kitchentype::select('kitchentype')->find($val);
                    }
                    $value['geographicalCuisine'] = $geographicalCuisine;
                }

                if ($chefData->chefAvailibilityWeek && in_array($req->todaysWeekDay, $chefData->chefAvailibilityWeek)) {
                    // Determine availability based on the condition
                    $value['available'] = $req->todaysWeekDay ? in_array($req->todaysWeekDay, $value->foodAvailibiltyOnWeekdays) : false;
                } else {
                    $value['available'] = false;
                }
            }
            $chefData = Chef::select('rating')->where('id', $req->chef_id)->first();
            return response()->json(["data" => $data, 'rating' => $chefData->rating, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function getFoodItem(Request $req)
    {
        if (!$req->food_id) {
            return response()->json(["message" => 'Please fill all the details', 'success' => false]);
        }
        try {
            $data = FoodItem::where('id', $req->food_id)->first();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getFoodItemWithoutStatus(Request $req)
    {
        try {
            // Fetch all food items where 'approved_status' is not 'approved'
            $data = FoodItem::where('approved_status', '!=', 'approved')->orderBy('created_at', 'desc')->get();

            return response()->json(["data" => $data, 'message' => 'Food List fetched successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateWeekAvailibilty(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                'food_id' => 'required',
                'weekAvailibilty' => 'required',
            ],
            [
                'food_id.required' => 'Please mention dish name',
                'weekAvailibilty.required' => 'Please mention week availibilty',
            ]
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            FoodItem::where('id', $req->food_id)->update(['foodAvailibiltyOnWeekdays' => $req->weekAvailibilty]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function addNewAlternativeContact(Request $req)
    {
        if (!$req->mobile) {
            return response()->json(['message' => 'Please fill mobile field', 'success' => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $newAlternativeContact = new ChefAlternativeContact();
            $newAlternativeContact->chef_id = $chef->id;
            $newAlternativeContact->mobile = str_replace("-", "", $req->mobile);
            $newAlternativeContact->save();
            return response()->json(['message' => 'Alternative contact number has been added successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getAllAlternativeContacts(Request $req)
    {
        // if (!$req->chef_id) {
        //     return response()->json(['message' => 'Please fill all the required fields', 'success' => false], 400);
        // }
        try {
            $chef = auth()->guard('chef')->user();
            return response()->json(['data' => ChefAlternativeContact::where('chef_id', $chef->id)->get(), 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateStatusOfAlternativeContact(Request $req)
    {
        if (!$req->id) {
            return response()->json(['message' => 'Please fill all the required fields', 'success' => false], 400);
        }
        try {
            ChefAlternativeContact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function changePasswordForChef(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                'chef_id' => 'required',
                'currentPassword' => 'required',
                'newPassword' => 'required',
                'confirmPassword' => 'required',
            ],
            [
                'chef_id.required' => 'Please mention chef_id',
                'currentPassword.required' => 'Please mention current password',
                'newPassword.required' => 'Please mention new password',
                'confirmPassword.required' => 'Please mention confirm password',
            ]
        );

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            if ($req->newPassword !== $req->confirmPassword) {
                return response()->json(['message' => 'New and Confirm password should be the same.', 'success' => false], 500);
            }
            $chefDetail = Chef::find($chef->id);
            if ($chefDetail) {
                $chefDetail->makeVisible('password');
                if (Hash::check($req->currentPassword, $chefDetail['password'])) {
                    $chefDetail->makeHidden('password');
                    Chef::where('id', $chef->id)->update(['password' => Hash::make($req->newPassword)]);
                    return response()->json(['message' => 'Your password has been changed successfully. ', 'success' => false], 200);
                } else {
                    return response()->json(['message' => 'Invalid Password ! ', 'success' => false], 500);
                }
            } else {
                return response()->json(['message' => 'Invalid Password ! ', 'success' => false], 500);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function sendProfileForReview(Request $req)
    {
        try {
            $chef = auth()->guard('chef')->user();
            $newProfileForReview = new ChefProfileReviewByAdmin();
            $newProfileForReview->chef_id = $chef->id;
            $newProfileForReview->save();
            Chef::where('id', $chef->id)->update(['status' => 2]);
            RequestForUpdateDetails::where(['chef_id' => $chef->id, 'status' => 1])->update(['status' => 2]);

            $chef = Chef::where('id', $chef->id)->first();
            // Log::info($chef);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new ChefSendReviewToAdmin($chef));
            }

            return response()->json(['message' => 'Request Submitted successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function requestForUpdate(Request $req)
    {
        if (!$req->request_for || !$req->message) {
            return response()->json(['message' => 'Please fill all the required fields', 'success' => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $alreadyPending = RequestForUpdateDetails::orderBy('created_at', 'desc')->where('chef_id', $chef->id)->whereIn('status', [0, 1])->first();
            if ($alreadyPending) {
                return response()->json(['message' => 'Action cannot be completed . Please wait till you previous request is processed.', 'success' => false], 500);
            }
            $newRequest = new RequestForUpdateDetails();
            $newRequest->chef_id = $chef->id;
            $newRequest->request_for = $req->request_for;
            $newRequest->message = $req->message;
            $newRequest->save();

            $request_query = RequestForUpdateDetails::orderBy('created_at', 'desc')->where('chef_id', $chef->id)->with('chef:id,firstName,lastName')->first();
            Log::info($request_query);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new RequestQueryNotification($request_query));
            }
            return response()->json(['message' => 'Your request has been sent to Homeplate. It will be processed shortly.', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getAllPendingRequest(Request $req)
    {
        try {
            $totalRecords = RequestForUpdateDetails::where('status', 0)->count();
            $skip = $req->page * 10;
            $data = RequestForUpdateDetails::with(['chef', 'chef.alternativeContacts'])->where('status', 0)->skip($skip)->take(10)->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }


    function getApprovedUpdaterequest(Request $req)
    {
        try {
            $chef = auth()->guard('chef')->user();
            $data = RequestForUpdateDetails::orderBy('created_at', 'desc')->where(['chef_id' => $chef->id, 'status' => 1])->first();
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function updateChefAvailibilty(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                'chef_id' => 'required',
                'chefAvailibilityWeek' => 'required',
                'chefAvailibilityFromTime' => 'required',
                'chefAvailibilityToTime' => 'required',
                'chefAvailibilityStatus' => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" => 'Please fill all required fields', "success" => false], 400);
        }

        try {
            Chef::where('id', $req->chef_id)->update([
                'chefAvailibilityWeek' => $req->chefAvailibilityWeek,
                'chefAvailibilityFromTime' => $req->chefAvailibilityFromTime,
                'chefAvailibilityToTime' => $req->chefAvailibilityToTime,
                'chefAvailibilityStatus' => $req->chefAvailibilityStatus
            ]);
            return response()->json(['message' => 'Availabilty updated successfully', 'success' => false], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function VerifyChefEmail(Request $req)
    {
        try {
            $chef = auth()->guard('chef')->user();
            $checkVerification = Chef::find($chef->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                Chef::where('id', $chef->id)->update(['email_verified_at' => Carbon::now(), 'is_email_verified' => 1]);
                $chefDetails = Chef::find($chef->id);
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($chefDetails->email))->send(new HomeshefChefEmailVerifiedSuccessfully($chefDetails));
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

    function chefRegisterationRequest(Request $req)
    {
        try {
            $chefRequestExist = ShefRegisterationRequest::where("email", $req->email)->first();
            if ($chefRequestExist) {
                return response()->json(['message' => "This email has already rerquest for register Please use another email!", "success" => false], 400);
            }
            $chefRequestExist = ShefRegisterationRequest::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefRequestExist) {
                return response()->json(['message' => "This mobile no has already rerquest for register Please use another mobileno!", "success" => false], 400);
            }
            $chef = new ShefRegisterationRequest();
            $chef->firstName = ucfirst($req->firstName);
            $chef->lastName = ucfirst($req->lastName);
            $chef->date_of_birth = $req->date_of_birth;
            $chef->mobile = str_replace("-", "", $req->mobile);
            $chef->email = $req->email;
            $chef->address_line = $req->address_line;
            $chef->state = $req->state;
            $chef->city = $req->city;
            $chef->kitchen_types = $req->kitchen_types;
            $chef->postal_code = str_replace(" ", "", strtoupper($req->postal_code));
            $chef->save();
            $chefDetail = ShefRegisterationRequest::find($chef->id);
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new ChefRegisterationRequest($chefDetail));
            }
            return response()->json(['message' => 'Request sent to Homeplate', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getChefRegisterationRequest(Request $req)
    {
        try {
            $totalRecords = ShefRegisterationRequest::count();
            $skip = $req->page * 10;
            $data = ShefRegisterationRequest::skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function updateFoodItemAppprovedStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "approved_status" => 'required',
        ], [
            "id.required" => "Please fill food id",
            "approved_status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            FoodItem::where('id', $req->id)->update(['approved_status' => $req->approved_status, 'approvedAt' => Carbon::now()->toDateTimeString()]);
            $foodItem = FoodItem::with('chef:firstName,lastName,email,id')->find($req->id);
            $chef = Chef::find($foodItem->chef['id']);
            $chefDetail = [
                'food_id' => $foodItem['id'],
                'id' => $foodItem['chef']['id'],
                'email' => $foodItem['chef']->email,
                'firstName' => ucfirst($foodItem['chef']->firstName),
                'lastName' => ucfirst($foodItem['chef']->lastName),
                'food_name' => $foodItem['dish_name'],
            ];
            if ($foodItem['approved_status'] == 'approved') {
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($chefDetail['email']))->send(new HomeshefFoodItemStatusChange($chefDetail));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }
            $chef->notify(new foodItemstatusChangeMail($chefDetail));
            return response()->json(["message" => 'Updated successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function sendRequestForChefReviewDelete(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "user_id" => 'required',
            "review_id" => 'required',
            "reason" => 'required',
        ], [
            "chef_id.required" => "Please fill chef id",
            "user_id.required" => "Please fill user id",
            "review_id.required" => "Please fill review id",
            "reason.required" => "Please fill reason",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $request = new ChefReviewDeleteRequest();
            $request->chef_id = $chef->id;
            $request->user_id = $req->user_id;
            $request->review_id = $req->review_id;
            $request->reason = $req->reason;
            $request->save();
            ChefReview::where('id', $req->review_id)->update(['requestedForDeletion' => 1]);
            $chefReviewDeleteRequest = ChefReviewDeleteRequest::with('chef')->orderByDesc('created_at')->where(['chef_id' => $chef->id, 'user_id' => $req->user_id, 'review_id' => $req->review_id])->first();
            Log::info($chefReviewDeleteRequest);
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new requestForChefReviewDelete($chefReviewDeleteRequest));
            }
            return response()->json(["message" => 'Request has been raised successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function sendRequestForUserBlacklist(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "user_id" => 'required',
            "reason" => 'required',
            "review_id" => 'required',
        ], [
            "chef_id.required" => "Please fill chef id",
            "user_id.required" => "Please fill user id",
            "review_id.required" => "Please fill review id",
            "reason.required" => "Please fill reason",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = auth()->guard('chef')->user();
            $request = new RequestForUserBlacklistByChef();
            $request->chef_id = $chef->id;
            $request->user_id = $req->user_id;
            $request->reason = $req->reason;
            $request->save();

            $blacklistRequest = RequestForUserBlacklistByChef::with(['user', 'chef'])->orderByDesc('created_at')->where(['chef_id' => $chef->id, 'user_id' => $req->user_id])->first();
            ChefReview::where('id', $req->review_id)->update(['requestedForBlackList' => 1]);
            $request = new Request();
            $request->merge([
                "chef_id" => $chef->id,
                "user_id" => $req->user_id,
                "review_id" => $req->review_id,
                "reason" => $req->reason
            ]);
            $this->sendRequestForChefReviewDelete($request);
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new requestForBlacklistUser($blacklistRequest));
            }
            return response()->json(["message" => 'Request has been raised successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteMyFoodItem(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = FoodItem::where('id', $req->id)->first();
            $images = json_decode($data->dishImage);
            str_replace(env('filePath'), '', $images);
            if (file_exists(str_replace(env('filePath'), '', $images))) {
                unlink(str_replace(env('filePath'), '', $images));
            }

            $img = json_decode($data->dishImageThumbnail);
            str_replace(env('filePath'), '', $img);
            if (file_exists(str_replace(env('filePath'), '', $img))) {
                unlink(str_replace(env('filePath'), '', $img));
            }
            FoodItem::where('id', $req->id)->delete();
            return response()->json(['message' => 'Item deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function addChefSuggestions(Request $req)
    {
        if (!file_exists('storage/chef/suggestions')) {
            mkdir('storage/chef/suggestions', 0755, true);
        }
        try {
            $chef = auth()->guard('chef')->user();
            DB::beginTransaction();
            $storedPath = $req->file('sample_pic')->store('chef/suggestions', 'public');
            $filename = asset('/storage', $storedPath);

            $chefsuggestion = new ChefSuggestion();
            $chefsuggestion->related_to = $req->related_to;
            $chefsuggestion->message = $req->message;
            $chefsuggestion->sample_pic = $filename;
            $chefsuggestion->chef_id = $chef->id;
            $chefsuggestion->save();

            $chef = Chef::find($chef->id);
            $chefDetail['id'] = $chef->id;
            $chefDetail['firstName'] = $chef->firstName;
            $chefDetail['lastName'] = $chef->lastName;

            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new chefSuggestionNotifications($chefDetail));
            }

            DB::commit();
            return response()->json(['message' => 'Suggestion Added successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateChefTaxInformation(Request $req)
    {
        try {
            DB::beginTransaction();
            $chef = auth()->guard('chef')->user();
            // Retrieve the chef's data
            $chef = Chef::find($chef->id);


            if (!$chef) {
                return response()->json(['message' => 'Chef not found', 'success' => false], 404);
            }

            if ($req->is_taxable != '1') {
                $chef->is_taxable = $req->is_taxable;
                $chef->save();
                return response()->json(['message' => 'Chef is ' . ($req->is_taxable == '1' ? 'taxable' : 'non taxable') , 'success' => false], 500);
            }

            // Store new GST image
            if ($req->hasFile('gst_image')) {
                $file = $req->file('gst_image');
                $s3 = AwsHelper::cred();
                // If there's already an image saved, delete it
                if (!empty($chef->gst_image)) {
                    $parsedUrl = parse_url($chef->gst_image, PHP_URL_PATH);
                    $oldKey = ltrim($parsedUrl, '/'); // remove leading slash

                    try {
                        $s3->deleteObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key'    => $oldKey,
                        ]);
                        Log::info('Old GST image deleted from S3', ['key' => $oldKey]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete old GST image from S3', ['error' => $e->getMessage()]);
                    }
                }
                // Upload new image
                $fileName = $file->store('/chef/TaxInformation');
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($file->getPathname(), 'r'),
                    'ContentType' => $file->getMimeType(),
                ]);

                $url = $result['ObjectURL'];

                Log::info('S3 Upload Success', ['url' => $url]);

                // Save URL to database
                $chef->gst_image = $url;
            }

            // Store new QST image
            if ($req->hasFile('qst_image')) {
                $file = $req->file('qst_image');
                $s3 = AwsHelper::cred();
                // If there's already an image saved, delete it
                if (!empty($chef->qst_image)) {
                    $parsedUrl = parse_url($chef->qst_image, PHP_URL_PATH);
                    $oldKey = ltrim($parsedUrl, '/'); // remove leading slash

                    try {
                        $s3->deleteObject([
                            'Bucket' => env('AWS_BUCKET'),
                            'Key'    => $oldKey,
                        ]);
                        Log::info('Old QST image deleted from S3', ['key' => $oldKey]);
                    } catch (\Exception $e) {
                        Log::error('Failed to delete old QST image from S3', ['error' => $e->getMessage()]);
                    }
                }
                // Upload new image
                $fileName = $file->store('/chef/TaxInformation');
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($file->getPathname(), 'r'),
                    'ContentType' => $file->getMimeType(),
                ]);

                $url = $result['ObjectURL'];

                Log::info('S3 Upload Success', ['url' => $url]);

                // Save URL to database
                $chef->qst_image = $url;
            }

            // Update GST and QST numbers
            $chef->gst_no = $req->gst_no;
            $chef->qst_no = $req->qst_no;
            $chef->save();

            DB::commit();

            return response()->json(['message' => "Tax Information Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Something went wrong ! Trying editting the tax details again', 'success' => false], 500);
        }
    }

    public function getChefOrders(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $query = SubOrders::query();
            $query->where('chef_id', $req->chef_id)->with('Orders.user', 'OrderItems.foodItem', 'OrderTrack', 'chefs')->whereHas('Orders', function ($subQuery) {
                $subQuery->where('payment_status', 'Paid');
            });

            if ($req->filter) {
                if ($req->from_date) {
                    $query->whereDate('created_at', '>=', $req->from_date);
                }

                if ($req->to_date) {
                    $query->whereDate('created_at', '<=', $req->to_date);
                }

                if (!empty($req->user_id)) { // Check if user_id is not empty
                    $query->whereHas('Orders.user', function ($subQuery) use ($req) {
                        $subQuery->where('user_id', $req->user_id);
                    });
                }
            }

            if ($req->status == 'Accepted') {
                $query->where('status', '!=', 'Pending')->where('status', '!=', "Rejected");
            }
            if ($req->status == 'Pending' || $req->status == 'Rejected') {
                $query->where('status', $req->status);
            }
            $query->orderBy('created_at', 'desc');
            $data = $query->get();
            $data->transform(function ($subOrder) {
                if ($subOrder->chef_commission_taxes) {
                    $subOrder->chef_commission_taxes = json_decode($subOrder->chef_commission_taxes, true);
                }
                return $subOrder;
            });
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    public function getChefSubOrder(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = SubOrders::where('chef_id', $req->chef_id)->where('sub_order_id', $req->sub_order_id)->with('orderItems.foodItem')->with('Orders')->first();
            $customer = User::where('id', $data->orders->user_id)->first();
            $trackDetails = OrderTrackDetails::where('track_id', $data->track_id)->first();
            Log::info($trackDetails);
            return response()->json(["data" => $data, "customer" => $customer, "trackDetails" => $trackDetails, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    public function updateChefOrderStatus(Request $req)
    {

        $validator = Validator::make($req->all(), [
            "track_id" => 'required',
            "status" => 'required|in:3,4,5,6,2',
            'sub_order_id' => 'required|exists:sub_orders,sub_order_id',
        ], [
            "track_id.required" => "Please fill track_id",
            "status.required" => "Please fill status",
            "sub_order_id.required" => "Please fill sub order_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {

            $order_status = OrderStatus::where('id', $req->status)->first()->status;
            $track_status = [
                'approve' => 'Your request has been successfully approved and processed', // id is 3
                'cancel' => 'Unfortunately, your request has been declined after review', // id is 6
                'pending' => 'Your request is currently under review; please wait patiently', // id is 2
                'preparing' => "The chef has begin preparing your order", // id is 5
                'ready to move' => "The chef has completed the preparation of your order. It is now ready to be delivered." // id is 4
            ];
            // if ($req->status == '3') {
            //     $deliveryToken = SubOrders::generateUniquerDeliveryToken();

            //     SubOrders::where('sub_order_id', $req->sub_order_id)->update(['delivery_token' => $deliveryToken]);
            // }
            SubOrders::where('sub_order_id', $req->sub_order_id)->update(['status' => $req->status]);
            $track_id = SubOrders::where('sub_order_id', $req->sub_order_id)->first()->track_id;
            Log::info($track_id);
            OrderTrackDetails::create([
                'track_id' => $track_id,
                'status' => $order_status,
                'track_desc' => $track_status[$order_status],
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function addChefStory(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "story" => 'required',
            'story_img' => 'required',
        ], [
            "chef_id.required" => "Please fill track_id",
            "story.required" => "Please fill status",
            "story_img.required" => "Please fill sub order_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = 'chef/' . $req->chef_id;
            $chef = Chef::find($req->chef_id);
            if ($req->hasFile('story_img')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->story_img))) {
                    unlink(str_replace(env('filePath'), '', $chef->story_img));
                }
                $storedPath = $req->file('story_img')->store($path, 'public');
                Chef::where("id", $req->chef_id)->update(["story_img" => asset('storage/' . $storedPath), "story" => $req->story]);
                return response()->json(["message" => "Updated successfully", "success" => true], 200);
            } else {
                return response()->json(["message" => "Please upload image", "success" => false], 500);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAllChefsStory(Request $req)
    {
        try {
            $totalRecords = User::whereNotNull('story')->count();
            $skip = $req->page * 10;
            $data = User::whereNotNull('story')->orderBy('created_at', 'desc')->skip($skip)->take(10)->get();
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

    function updateFoodCertificateStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required|exists:chefs,id',
            "is_hfc_paid" => 'nullable',
            "is_rrc_paid" => 'nullable',
        ], [
            "chef_id.required" => "Please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            // Chef::where('id', $req->id)->update([('is_hfc_paid' => $req->is_hfc_paid) || ('is_rrc_paid' => $req->is_rrc_paid)]);
            $chefDetail = Chef::find($req->chef_id);
            if (!$chefDetail) {
                return response()->json(['message' => "Chef not found", "success" => false, 'data' => ''], 200);
            }
            if ($req->has('is_hfc_paid')) {
                $chefDetail->is_hfc_paid = $req->is_hfc_paid;
            }
            if ($req->has('is_rrc_paid')) {
                $chefDetail->is_rrc_paid = $req->is_rrc_paid;
                $chefDetail->notify(new FoodCertificateNotification($chefDetail));
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($chefDetail->email))->send(new FoodCertificateMail($chefDetail));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
            }
            // Save the changes
            $chefDetail->save();
            return response()->json(['message' => "Food Certificate Status Updated Successfully", "success" => true, 'data' => $chefDetail], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function foodLicense(Request $request)
    {
        $validator = Validator::make($request->all(), [
            // 'chef_id' => 'required',
            'signature' => 'required|image|mimes:jpeg,png,jpg|max:100'
        ], [
            // 'chef_id.required' => 'please fill the chef_id',
            'signature.required' => 'Please upload a signature image.',
            'signature.image' => 'The uploaded file must be an image.',
            'signature.mimes' => 'Only JPEG, JPG and PNG formats are allowed for the signature image.',
            'signature.max' => 'The signature image size must not exceed 100KB.'
        ]);
        if ($validator->fails())
            return response()->json(['success' => false, 'error' => $validator->errors()->first()], 400);

        if (!file_exists('storage/chef/food_license')) {
            mkdir('storage/chef/food_license', 0755, true);
        }
        try {
            $chef = auth()->guard('chef')->user();
            if ($request->has('chef_id')) {
                return response()->json(["message" => 'Bad Request', "success" => false], 500);
            }
            $existFoodLicense = FoodLicense::where('chef_id', $chef->id)->first();
            if ($existFoodLicense) {
                // If a food license already exists for the chef, return response indicating duplicate submission
                return response()->json(['success' => false, 'message' => 'You have already submitted the form', 'data' => ''], 400);
            }
            $existMobile = FoodLicense::where('business_mobile', str_replace("-", "", $request->business_mobile))->first();
            if ($existMobile) {
                return response()->json(["message" => 'Business Mobile No already registered', "success" => false], 500);
            }
            $existCompMobile = FoodLicense::where('company_mobile', str_replace("-", "", $request->company_mobile))->first();
            if ($existCompMobile) {
                return response()->json(["message" => 'Company Mobile No already registered', "success" => false], 500);
            }
            if ($request->hasFile('signature')) {
                $storedPath = $request->file('signature')->store('chef/food_license', 'public');
                // Generate a URL to access the stored file
                $filename = Storage::url($storedPath);
            } else {
                return response()->json(['message' => 'please upload signature image', 'success' => false], 400);
            }
            DB::beginTransaction();

            $foodLicense = new FoodLicense();
            $foodLicense->chef_id = $chef->id;
            $foodLicense->status = '0'; //0->verification_pending, 1->verified by homeplate, 2->submited to govt, 3->issued by govt, 4->rejected by govt, 5->wrong data submited
            // $foodLicense->flag = $request->flag;
            $foodLicense->business_name = $request->business_name;
            $foodLicense->business_mobile = $request->business_mobile;
            $foodLicense->civic_number = $request->civic_number;
            $foodLicense->street_name = $request->street_name;
            $foodLicense->city = $request->city;
            $foodLicense->postal_code = $request->postal_code;
            $foodLicense->vehicle_number = $request->vehicle_number;
            $foodLicense->start_date = $request->start_date;
            $foodLicense->owner_name = $request->owner_name;
            $foodLicense->company_name = $request->company_name;
            $foodLicense->enterprise_number = $request->enterprise_number;
            $foodLicense->company_mobile = $request->company_mobile;
            $foodLicense->applicant_civic_number = $request->applicant_civic_number;
            $foodLicense->applicant_street_name = $request->applicant_street_name;
            $foodLicense->applicant_city = $request->applicant_city;
            $foodLicense->applicant_postal_code = $request->applicant_postal_code;
            $foodLicense->applicant_province = $request->applicant_province;
            $foodLicense->applicant_country = $request->applicant_country;
            $foodLicense->catering_general = $request->catering_general;
            $foodLicense->catering_hot_cold = $request->catering_hot_cold;
            $foodLicense->catering_buffet = $request->catering_buffet;
            $foodLicense->catering_maintaining = $request->catering_maintaining;
            $foodLicense->retail_general = $request->retail_general;
            $foodLicense->retail_maintaining = $request->retail_maintaining;
            $foodLicense->annual_rate = $request->annual_rate;
            $foodLicense->facility_dedicated = $request->facility_dedicated;
            $foodLicense->sink_area_premises = $request->sink_area_premises;
            $foodLicense->potable_water_access = $request->potable_water_access;
            $foodLicense->regulatory_dispenser = $request->regulatory_dispenser;
            $foodLicense->recovery_evacuation = $request->recovery_evacuation;
            $foodLicense->ventilation_system = $request->ventilation_system;
            $foodLicense->waste_container = $request->waste_container;
            $foodLicense->manager_name = $request->manager_name;
            $foodLicense->manager_number = $request->manager_number;
            $foodLicense->additional_unit = $request->additional_unit;
            $foodLicense->total_unit = $request->total_unit;
            $foodLicense->total_amount = $request->total_amount;
            $foodLicense->applicant_name = $request->applicant_name;
            // $foodLicense->signature = $request->signature;
            $foodLicense->signature = $filename;
            $foodLicense->declaration_date = $request->declaration_date;
            $foodLicense->message = $request->message;
            $foodLicense->save();
            DB::commit();
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($foodLicense->chef->email))->send(new FoodLicenseEmail($foodLicense));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new ChefFoodLicense($foodLicense));
            }

            return response()->json(['message' => 'Food license form submited successfully', 'food_license' => $foodLicense], 200);
        } catch (\Exception $e) {
            Log::error($e);
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Opps! Something went wrong, Please try again !', 'data' => ''], 500);
        }
    }

    // Get Food License with Chef Data In Single API
    // public function chefFoodLicense(Request $request)
    // {
    //     try {
    //         $chefs = FoodLicense::with(['chef:id,firstName,lastName,email,mobile'])->get();

    //         $chefStatus = [
    //             0 => 'Verification Pending',
    //             1 => 'Verified by Homeplate',
    //             2 => 'Submitted to Govt',
    //             3 => 'Issued by Govt',
    //             4 => 'Rejected by Govt',
    //             5 => 'Wrong Data Submitted',
    //         ];
    //         $chefs->transform(function ($chef) use ($chefStatus) {
    //             $chef->status = $chefStatus[$chef->status];
    //             return $chef;
    //         });

    //         return response()->json(['message' => 'Food license data retrieved successfully', 'food_license' => $chefs], 200);
    //     } catch (\Exception $e) {
    //         Log::error($e);
    //         return response()->json(['success' =>` false, 'message' => 'Oops! Something went wrong. Please try again.'], 500);
    //     }
    // }

    public function getFoodLicenseList(Request $request)
    {
        try {
            $chefs = FoodLicense::with(['chef:id,firstName,lastName,email,mobile'])->get();

            $chefStatus = [
                0 => 'Verification Pending',
                1 => 'Verified by Homeplate',
                2 => 'Submitted to Govt',
                3 => 'Issued by Govt',
                4 => 'Rejected by Govt',
                5 => 'Wrong Data Submitted',
            ];
            $chefs->transform(function ($chef) use ($chefStatus) {
                $chef->status = $chefStatus[$chef->status];
                return $chef;
            });

            return response()->json(['message' => 'Food license data retrieved successfully', 'food_license' => $chefs], 200);
        } catch (\Exception $e) {
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong. Please try again.'], 500);
        }
    }


    public function getFoodLicenseData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:food_licenses,id',
        ], [
            'id.required' => 'Please provide the food license ID.',
            'id.exists' => 'provide correct ID.'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }

        try {
            $data = FoodLicense::find($request->id);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Request Not Found', 'data' => ''], 400);
            }

            return response()->json(['success' => true, 'message' => 'Fetch Food License Data', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong. Please try again.'], 500);
        }
    }

    public function deleteChef(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:chefs,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {
            $chef = Chef::find($request->id);
            $chef->delete();

            return response()->json(['success' => true, 'message' => 'Chef deleted successfully'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            // If chef is not found or deletion fails, return error response
            return response()->json(['success' => false, 'message' => 'Failed to delete chef'], 500);
        }
    }


    public function packageInstructionPDF(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'chef_id' => 'required|exists:chefs,id',
            'id' => 'required|exists:food_items,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {
            // Retrieve the food item
            $foodItem = FoodItem::where('chef_id', $req->chef_id)->find($req->id);
            if (!$foodItem) {
                return response()->json(['success' => false, 'message' => 'Request Not Found', 'data' => ''], 400);
            }
            $pdf = Pdf::loadView('pdf.packing-label', compact('foodItem'));

            // Set paper size and orientation to A4 portrait
            // $pdf->setPaper('a4', 'portrait');
            $pdf->getDomPDF()->setPaper([0, 0, 288, 432], 'portrait');
            // $pdf->setPaper(array(0,0,288,432));

            // Return the PDF as a downloadable file
            return $pdf->download('packing-label.pdf');
            // return response()->json(['success' => true, 'message' => 'Package Instruction PDF Downloaded'], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['message' => 'Failed to generate PDF.', 'error' => $e->getMessage()], 500);
        }
    }

    function deletePendingRequest(Request $req)
    {
        DB::beginTransaction(); // Start transaction
        try {
            $pendingRequest = RequestForUpdateDetails::find($req->id);
            Log::info($pendingRequest);
            if (!$pendingRequest) {
                return response()->json(['success' => false, 'message' => 'Chef pending request not found'], 400);
            }
            $pendingRequest->delete();
            DB::commit(); // Commit transaction if no errors
            return response()->json(['success' => true, 'message' => 'Pending request deleted successfully!'], 200);
        } catch (\Exception $th) {
            DB::rollback(); // Rollback transaction on error
            Log::error("Error in deletePendingRequest: " . $th->getMessage()); // Improved logging
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function generatePDF(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:food_licenses,id',
        ], [
            'id.required' => 'Please provide the food license ID.',
            'id.exists' => 'provide correct ID.'
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }
        try {
            $data = FoodLicense::find($request->id);

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Request Not Found', 'data' => ''], 400);
            }
            $pdf = Pdf::loadView('pdf.food-license', compact('data'));

            $pdf->set_option('isRemoteEnabled', true);
            // Set paper size and orientation to A4 portrait
            $pdf->setPaper('a4', 'portrait');

            // Return the PDF as a downloadable file
            return $pdf->download('rrc_form.pdf');

            // return response()->json(['success' => true, 'message' => 'Fetch Food License Data', 'data' => $data], 200);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error($e);
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong. Please try again.'], 500);
        }
    }


    public function orderInvoicePDF(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'chef_id' => 'required|exists:chefs,id',
            'sub_order_id' => 'required|exists:sub_orders,sub_order_id'
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => $validator->errors()->first()], 400);
        }
        try {
            $data = SubOrders::where('chef_id', $req->chef_id)
                ->where('sub_order_id', $req->sub_order_id)
                ->with(['Orders', 'orderItems.foodItem', 'chefs'])
                ->first();

            if (!$data) {
                return response()->json(['success' => false, 'message' => 'Sub order not found'], 404);
            }

            $pdf = Pdf::loadView('pdf.order-invoice', compact('data'));
            $pdf->getDomPDF()->setPaper('a4', 'portrait');
            return $pdf->download('customer-order-invoice.pdf');
            // return response($pdf->download('order-invoice.pdf'))
            //     ->header('Content-Type', 'application/pdf')
            //     ->header('success', 'true') // Custom header to indicate success
            //     ->header('message', 'Your invoice has been downloaded successfully!');

            // return $pdf->download('order-invoice.pdf');
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'error' => $th->getMessage(), 'success' => false]);
        }
    }

    public function getSubOrderAcceptedByChef(Request $req)
    {
        try {
            // Fetch sub-orders with status "accepted" or '3'
            $subOrders = SubOrders::whereIn('status', ['accepted', '3'])->get();

            return response()->json(['success' => true, 'data' => $subOrders], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong.'], 500);
        }
    }
}
