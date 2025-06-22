<?php

namespace App\Http\Controllers\admins;

use App\Helpers\AwsHelper;
use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Mail\MessageFromHomeplateTeamToChef;
use App\Models\Admin;
use App\Models\Adminsetting;
use App\Models\Allergy;
use App\Models\Chef;
use App\Models\ChefReview;
use App\Models\ChefReviewDeleteRequest;
use App\Models\ChefSuggestion;
use App\Models\Contact;
use App\Models\Dietary;
use App\Models\Driver;
use App\Models\FoodCategory;
use App\Models\HeatingInstruction;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\OrderTrackDetails;
use App\Models\PackingTemplates;
use App\Models\RequestForUpdateDetails;
use App\Models\RequestForUserBlacklistByChef;
use App\Models\Sitesetting;
use App\Models\SubOrders;
use App\Models\User;
use App\Notifications\Chef\ChefEmailNotification;
use App\Notifications\Chef\NewChefReviewNotification;
use App\Notifications\Chef\NewReviewNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\Paginator;
use PDF;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;
// use Intervention\Image\Image; //Intervention Image
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth;


class AdminController extends Controller
{
    function adminRegistration(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "firstName" => 'required',
            "lastName" => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            "status" => 'required',
        ], [
            "firstName.required" => "Please fill first name",
            "lastName.required" => "Please fill last name",
            "email.required" => "Please fill email",
            "password.required" => "Please fill password",
            "status.required" => "Please fill status",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $adminExist = Admin::where("email", $req->email)->first();
            if ($adminExist) {
                return response()->json(["message" => 'Email is already Registered!', "success" => false], 400);
            }

            $admin = new Admin();
            $admin->firstName = $req->firstName;
            $admin->lastName = $req->lastName;
            $admin->email = $req->email;
            $admin->password = Hash::make($req->password);
            $admin->status = $req->status;
            $admin->save();
            DB::commit();
            return response()->json(["message" => "Registered successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function adminLogin(Request $req)
    {
        $rules = [
            'email' => 'required|email|exists:admins,email',
            'password' => 'required|min:8'
        ];
        $validate = Validator::make($req->all(), $rules);
        if ($validate->fails()) {
            return response()->json(["message" => $validate->errors()->first(), "success" => false], 400);
        }
        try {
            $adminDetail = Admin::where("email", $req->email)->first();
            if ($adminDetail) {
                $adminDetail->makeVisible('password');
                if (Hash::check($req->password, $adminDetail['password'])) {
                    if (!$token = auth('admin')->attempt(['email' => $req->email, 'password' => $req->password])) {
                        return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
                    }
                    // return Admin::createToken($token);
                    return response()->json(["message" => "You are logged in now !", 'admin_id' => $adminDetail->id, 'token' => Admin::createToken($token), "success" => true], 200);
                } else {
                    return response()->json(['message' => 'Invalid Password ! ', 'success' => false], 500);
                }
            }
            return response()->json(['message' => 'Login failed due to incorrect credentials ! ', 'success' => false], 500);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function adminProfile()
    {
        // Retrieve the authenticated user
        $admin = auth()->guard('admin')->user();
        // Check if the user is authenticated
        if (!$admin) {
            return response()->json(['message' => 'admin not found', 'success' => false], 404);
        }
        // Return the user's profile
        return response()->json(['success' => true, 'data' => $admin], 200);
    }

    public function adminLogout()
    {
        auth()->guard('admin')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function adminRefreshToken()
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

    public function updateSiteSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Sitesetting::where('id', $req->id)->first();
            if ($data) {
                $updateData = $req->all();
                Sitesetting::where('id', $req->id)->update($updateData);
                return response()->json(['message' => "Updated Successfully", "success" => true], 200);
            } else {
                $siteSeting = new Sitesetting();
                $siteSeting->phone_one = $req->phone_one;
                $siteSeting->phone_two = $req->phone_two;
                $siteSeting->email = $req->email;
                $siteSeting->company_name = $req->company_name;
                $siteSeting->company_address = $req->company_address;
                $siteSeting->copyright = $req->copyright;
                $siteSeting->facebook = $req->facebook;
                $siteSeting->instagram = $req->instagram;
                $siteSeting->twitter = $req->twitter;
                $siteSeting->youtube = $req->youtube;
                $siteSeting->created_by_company_link = $req->created_by_company_link;
                $siteSeting->created_by_company = $req->created_by_company;
                $siteSeting->save();
                return response()->json(['message' => "Added Successfully", "success" => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function deleteSiteSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Sitesetting::where('id', $req->id)->first();
            $data->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getSiteSettings(Request $req)
    {
        try {
            $totalRecords = Sitesetting::count();
            $skip = $req->page * 10;
            $data = Sitesetting::skip($skip)->take(10)->get();

            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // "default_comm" => 'required|regex:/^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$/',
            "default_comm" => 'required',
            'refugee_comm' => 'required',
            'singlemom_comm' => 'required',
            "lostjob_comm" => 'required',
            'student_comm' => 'required',
            'food_default_comm' => 'required',
            "radius" => 'required',
            "radiusForDriver" => 'required',
            "food_handler_certificate_cost" => 'required',
            "restaurant_and_retail_license_cost" => 'required',
            "certificate_handling_cost" => 'required',
        ], [
            "default_comm.required" => "Please fill default_comm",
            "default_comm.regex" => "Please enter valid default_comm",
            "refugee_comm.required" => "Please fill refugee_comm",
            "refugee_comm.regex" => "Please enter valid refugee_comm",
            "singlemom_comm.required" => "Please fill singlemom_comm",
            "singlemom_comm.regex" => "Please enter valid singlemom_comm",
            "lostjob_comm.required" => "Please fill lostjob_comm",
            "lostjob_comm.regex" => "Please enter valid lostjob_comm",
            "student_comm.required" => "Please fill student_comm",
            "student_comm.regex" => "Please enter valid student_comm",
            "food_default_comm.required" => "Please fill food_default_comm",
            "food_default_comm.regex" => "Please enter valid food_default_comm",
            "radius.required" => "Please fill radius",
            "radiusForDriver.required" => "Please fill radius for driver",
            "food_handler_certificate_cost.required" => "Please fill food handler certificate cost",
            "restaurant_and_retail_license_cost.required" => "Please fill restaurant and retail license cost",
            "certificate_handling_cost.required" => "Please fill certificate handling cost",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $adminSetting = new Adminsetting();
            $adminSetting->default_comm = $req->default_comm;
            $adminSetting->refugee_comm = $req->refugee_comm;
            $adminSetting->singlemom_comm = $req->singlemom_comm;
            $adminSetting->lostjob_comm = $req->lostjob_comm;
            $adminSetting->student_comm = $req->student_comm;
            $adminSetting->food_default_comm = $req->food_default_comm;
            $adminSetting->radius = $req->radius;
            $adminSetting->radiusForDriver = $req->radiusForDriver;
            $adminSetting->Work_with_us_content = $req->Work_with_us_content;
            $adminSetting->food_handler_certificate_cost = $req->food_handler_certificate_cost;
            $adminSetting->restaurant_and_retail_license_cost = $req->restaurant_and_retail_license_cost;
            $adminSetting->certificate_handling_cost = $req->certificate_handling_cost;

            $adminSetting->save();
            return response()->json(["message" => "Submitted successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }


    public function updateAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Log::info($req->all());
            // Check if the value exceeds 500
            // if ($req->all() > 500) {
            //     return response()->json(["message" => "The input value must be lower than 500.", "success" => false], 400);
            // }
            $updateData = $req->all();

            Adminsetting::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Adminsetting::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAdminSettings(Request $req)
    {
        try {
            $data = Adminsetting::latest()->first();
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "category" => 'required',
            "commission" => 'nullable|integer|between:1,100',
            "image" => 'required',
        ], [
            "category.required" => "Please fill category",
            "image.required" => "Please fill image",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if ($req->hasFile('image')) {
            $file = $req->file('image');
                $fileName = 'foodCategory/'  . time() . '_' . $file->getClientOriginalName();

                $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);

                $filename = $result['ObjectURL'];
        }
        try {
            $foodcategory = new FoodCategory();
            $foodcategory->category = $req->category;
            // Check if 'commission' is provided; if not, set a default value
            $foodcategory->commission = $req->commission ?? 10;

            $foodcategory->image = $filename;
            $foodcategory->save();
            return response()->json(["message" => "Submitted successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = FoodCategory::where('id', $req->id)->first();
            $updateData = [];
            if ($req->category) {
                $updateData['category'] = $req->category;
            }
            if ($req->commission) {
                $updateData['commission'] = $req->commission ? $req->commission : 10;
            }
            if ($req->hasFile('image')) {
                $file = $req->file('image');
                $fileName = 'foodCategory/'  . time() . '_' . $file->getClientOriginalName();

                $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($file->getPathname(), 'r'),
                        'ContentType' => $file->getMimeType(),
                    ]);

                $updateData['image'] = $result['ObjectURL'];
            }
            FoodCategory::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = FoodCategory::where('id', $req->id)->first();
            $images = json_decode($data->image);
            str_replace(env('filePath'), '', $images);
            if (file_exists(str_replace(env('filePath'), '', $images))) {
                unlink(str_replace(env('filePath'), '', $images));
            }
            FoodCategory::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "image" => 'required',
            "small_description" => 'required',
            "allergy_name" => 'required',
        ], [
            "image.required" => "Please fill image",
            "small_description.required" => "Please fill small_description",
            "allergy_name.required" => "Please fill allergy_name",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = "storage/admin/allergen_icons/";
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            DB::beginTransaction();

            if ($req->file('image') && isset($req->image)) {

                $big_image = $req->file('image');

                // Generate new image name
                $image_name = strtolower(trim($req->allergy_name));
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();

                // Ensure temp folder exists
                $tempFolder = storage_path('app/temp/');
                if (!File::exists($tempFolder)) {
                    File::makeDirectory($tempFolder, 0777, true);
                }

                // Save resized image temporarily
                $tempPath = $tempFolder . $name_gen;
                Image::make($big_image)->resize(200, 200)->save($tempPath);

                // Define S3 file name
                $fileName = 'allergen_icons/' . time() . '_' . $name_gen;

                $s3 = AwsHelper::cred();

                // Upload the resized image to S3
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($tempPath, 'r'),
                    'ContentType' => $big_image->getMimeType(),
                ]);


                $filename= $result['ObjectURL'];

            }
            Allergy::insert([
                'image' => $filename,
                'small_description' => $req->small_description,
                'allergy_name' => strtolower($req->allergy_name),
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(["message" => "Added successfully", "success" => true], 201);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $data = Allergy::where('id', $req->id)->first();
            $updateData = [];

            if ($req->hasFile('image')) {
                // Remove the existing image
                $images = $data->image;
                $imagePath = str_replace(env('filePath'), '', $images);

                if (File::exists(storage_path('app/' . $imagePath))) {
                    File::delete(storage_path('app/' . $imagePath));
                }

                // Upload new image
                $big_image = $req->file('image');

                // Generate new image name
                $image_name = strtolower(trim($req->allergy_name));
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();

                // Ensure temp folder exists
                $tempFolder = storage_path('app/temp/');
                if (!File::exists($tempFolder)) {
                    File::makeDirectory($tempFolder, 0777, true);
                }

                // Save resized image temporarily
                $tempPath = $tempFolder . $name_gen;
                Image::make($big_image)->resize(200, 200)->save($tempPath);

                // Define S3 file name
                $fileName = 'allergen_icons/' . time() . '_' . $name_gen;

                $s3 = AwsHelper::cred();

                // Upload the resized image to S3
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($tempPath, 'r'),
                    'ContentType' => $big_image->getMimeType(),
                ]);

                // Get the public URL of the uploaded file
                $updateData['image'] = $result['ObjectURL'];

                // Delete the local temp file
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }
            if ($req->small_description) {
                $updateData['small_description'] = $req->small_description;
            }
            if ($req->allergy_name) {
                $updateData['allergy_name'] = $req->allergy_name;
            }
            Allergy::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Allergy::where('id', $req->id)->first();
            $images = $data->image;
            str_replace(env('filePath'), '', $images);
            if (file_exists(str_replace(env('filePath'), '', $images))) {
                unlink(str_replace(env('filePath'), '', $images));
            }
            Allergy::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "diet_name" => 'required',
            "small_description" => 'required',
            "image" => 'required',
        ], [
            "diet_name.required" => "Please fill diet_name",
            "small_description.required" => "Please fill small_description",
            "image.required" => "Please fill image",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $path = "storage/admin/dietaries_icons/";
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            DB::beginTransaction();

            if ($req->hasFile('image')) {

                // Upload new image
                $big_image = $req->file('image');

                // Generate new image name
                $image_name = strtolower(trim($req->allergy_name));
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();

                // Ensure temp folder exists
                $tempFolder = storage_path('app/temp/');
                if (!File::exists($tempFolder)) {
                    File::makeDirectory($tempFolder, 0777, true);
                }

                // Save resized image temporarily
                $tempPath = $tempFolder . $name_gen;
                Image::make($big_image)->resize(200, 200)->save($tempPath);

                // Define S3 file name
                $fileName = 'dietaries/' . time() . '_' . $name_gen;

                $s3 = AwsHelper::cred();

                // Upload the resized image to S3
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($tempPath, 'r'),
                    'ContentType' => $big_image->getMimeType(),
                ]);

                // Get the public URL of the uploaded file
                $filename = $result['ObjectURL'];

                // Delete the local temp file
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }

            Dietary::insert([
                'diet_name' => strtolower($req->diet_name),
                'small_description' => $req->small_description,
                'image' => $filename,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(["message" => "Added successfully", "success" => true], 201);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if (!File::exists("storage/admin/dietaries_icons/")) {
            File::makeDirectory("storage/admin/dietaries_icons/", $mode = 0777, true, true);
        }
        try {
            $data = Dietary::where('id', $req->id)->first();
            $updateData = [];
            if ($req->hasFile('image')) {
                // Remove the existing image
                $images = $data->image;
                $imagePath = str_replace(env('filePath'), '', $images);

                if (File::exists(storage_path('app/' . $imagePath))) {
                    File::delete(storage_path('app/' . $imagePath));
                }

                // Upload new image
                $big_image = $req->file('image');

                // Generate new image name
                $image_name = strtolower(trim($req->allergy_name));
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();

                // Ensure temp folder exists
                $tempFolder = storage_path('app/temp/');
                if (!File::exists($tempFolder)) {
                    File::makeDirectory($tempFolder, 0777, true);
                }

                // Save resized image temporarily
                $tempPath = $tempFolder . $name_gen;
                Image::make($big_image)->resize(200, 200)->save($tempPath);

                // Define S3 file name
                $fileName = 'dietaries/' . time() . '_' . $name_gen;

                $s3 = AwsHelper::cred();

                // Upload the resized image to S3
                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($tempPath, 'r'),
                    'ContentType' => $big_image->getMimeType(),
                ]);

                // Get the public URL of the uploaded file
                $updateData['image'] = $result['ObjectURL'];

                // Delete the local temp file
                if (File::exists($tempPath)) {
                    File::delete($tempPath);
                }
            }
            if ($req->diet_name) {
                $updateData['diet_name'] = $req->diet_name;
            }
            if ($req->small_description) {
                $updateData['small_description'] = $req->small_description;
            }
            Dietary::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Dietary::where('id', $req->id)->first();
            $images = $data->image;
            str_replace(env('filePath'), '', $images);
            if (file_exists(str_replace(env('filePath'), '', $images))) {
                unlink(str_replace(env('filePath'), '', $images));
            }
            Dietary::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function addHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "title" => 'required',
            "description" => 'required',
        ], [
            "title.required" => "Please fill title",
            "description.required" => "Please fill description",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $heating = new HeatingInstruction();
            $heating->title = $req->title;
            $heating->description = $req->description;
            $heating->save();
            return response()->json(["message" => "Submitted successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = HeatingInstruction::where('id', $req->id)->first();
            $updateData = $req->all();
            HeatingInstruction::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = HeatingInstruction::where('id', $req->id)->first();
            HeatingInstruction::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateHeatingInstructionsStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "Please fill status",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->status == "0" || $req->status == "1") {
                $updateData['status'] = $req->status;
            }
            // $updateData = $req->status;
            HeatingInstruction::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function addIngredients(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "ing_name" => 'required',
        ], [
            "ing_name.required" => "Please fill ing_name",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Ingredient::where('ing_name', $req->ing_name)->first();
            if ($data) {
                return response()->json(["message" => "Ingredient Already exist", "success" => false], 500);
            } else {
                $ingredient = new Ingredient();
                $ingredient->ing_name = $req->ing_name;
                $ingredient->save();
                return response()->json(["message" => "Submitted successfully", "success" => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateIngredient(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Ingredient::where('id', $req->id)->first();
            $updateData = $req->all();
            Ingredient::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteIngredient(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "Please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Ingredient::where('id', $req->id)->first();
            Ingredient::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateIngredientStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "Please fill status",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->status == "0" || $req->status == "1") {
                $updateData['status'] = $req->status;
            }
            // $updateData = $req->status;
            Ingredient::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // public function sendMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "chef_id" => 'required',
    //         "subject" => 'required',
    //         "message" => 'required',
    //     ], [
    //         "chef_id.required" => "Please fill chef_id",
    //         "subject.required" => "Please fill subject",
    //         "message.required" => "Please fill message",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $contact = new Contact();
    //         $contact->chef_id = $req->chef_id;
    //         $contact->subject = $req->subject;
    //         $contact->message = $req->message;
    //         $contact->save();

    //         // $data = Chef::where('chef_id', $req->chef_id)->first();
    //         // $email = $data->email;
    //         // $mobile = $data->mobile;
    //         return response()->json(['message' => 'Message Sent Successfully', "success" => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    // public function updateMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //     ], [
    //         "id.required" => "Please fill id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $updateData = $req->all();
    //         Contact::where('id', $req->id)->update($updateData);
    //         return response()->json(['message' => "Updated Successfully", "success" => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    // public function deleteMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //     ], [
    //         "id.required" => "Please fill id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         Contact::where('id', $req->id)->delete();
    //         return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    // public function getMessageToChef(Request $req)
    // {
    //     try {
    //         $totalRecords = Contact::count();
    //         $skip = $req->page * 10;
    //         $data = Contact::skip($skip)->take(10)->get();
    //         return response()->json([
    //             'data' => $data,
    //             'TotalRecords' => $totalRecords,
    //             'success' => true
    //         ], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    function getAllUsers(Request $req)
    {
        try {
            $totalRecords = User::count();
            if ($req->list) {
                $data = User::select('id', 'firstName', 'lastName')->get();
            } else {
                // $skip = $req->page * 10;
                $data = User::orderBy('created_at', 'desc')
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

    // function getAllChefs(Request $req)
    // {
    //     try {
    //         if ($req->list)
    //         {
    //             $totalRecords = Chef::count();
    //             $data = Chef::select('id', 'firstName', 'lastName')->get();
    //         }
    //         else
    //         {
    //             $query = Chef::query();
    //             if ($req->postalCodes) {
    //                 $postalCodes = explode(',', $req->postalCodes);
    //                 $query->where(function ($q) use ($postalCodes) {
    //                     foreach ($postalCodes as $postalCode) {
    //                         $q->orWhere('postal_code', 'like', $postalCode . '%');
    //                     }
    //                 });
    //             }

    //             if ($req->kitchenTypes) {
    //                 $kitchenTypes = explode(',', $req->kitchenTypes);
    //                 $query->where(function ($q) use ($kitchenTypes) {
    //                     foreach ($kitchenTypes as $value) {
    //                         $q->whereJsonContains('kitchen_types', $value);
    //                     }
    //                 });
    //             }

    //             if ($req->weekDay_availibilty) {
    //                 $weekDay_availibilty = explode(',', $req->weekDay_availibilty);
    //                 Log::info($weekDay_availibilty);
    //                 $query->where(function ($q) use ($weekDay_availibilty) {
    //                     foreach ($weekDay_availibilty as $key => $value) {
    //                         $q->whereJsonContains('chefAvailibilityWeek', $value);
    //                     }
    //                 });
    //             }

    //             if (isset($req->status)) {
    //                 $query->where('status', $req->status);
    //             }
    //             $query->orderBy('created_at', 'desc')->withCount([
    //                 'foodItems as active_food_items_count' => function ($query) {
    //                     $query->where('approved_status', 'approved');
    //                 },
    //                 'foodItems as unapproved_food_items_count' => function ($query) {
    //                     $query->where('approved_status', 'unapproved');
    //                 }
    //             ])->with([
    //                 'chefDocuments' => fn ($q) => $q->select('id', 'chef_id', 'document_field_id', 'field_value')->with([
    //                     'documentItemFields' => fn ($qr) => $qr->select('id', 'document_item_list_id', 'field_name', 'type', 'mandatory')
    //                 ])
    //             ]);

    //             $skip = $req->page * 10;

    //             $data = $query->get();
    //             $totalRecords = $query->count();
    //         }

    //         return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => ($th->getMessage()).'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    public function getAllChefs(Request $req)
    {
        try {
            $query = Chef::query();

            // For list view, only select specific columns
            if (!empty($req->list)) {
                $query->select('id', 'firstName', 'lastName');
            } else {
                // Apply filters based on the request
                if (!empty($req->postalCodes)) {
                    $postalCodes = explode(',', $req->postalCodes);
                    $query->where(function ($q) use ($postalCodes) {
                        foreach ($postalCodes as $postalCode) {
                            $q->orWhere('postal_code', 'like', "$postalCode%");
                        }
                    });
                }

                if (!empty($req->kitchenTypes)) {
                    $kitchenTypes = explode(',', $req->kitchenTypes);
                    $query->where(function ($q) use ($kitchenTypes) {
                        foreach ($kitchenTypes as $kitchenType) {
                            $q->orWhereJsonContains('kitchen_types', $kitchenType);
                        }
                    });
                }

                if (!empty($req->weekDay_availibilty)) {
                    $weekDay_availibilty = explode(',', $req->weekDay_availibilty);
                    $query->where(function ($q) use ($weekDay_availibilty) {
                        foreach ($weekDay_availibilty as $day) {
                            $q->whereJsonContains('chefAvailibilityWeek', $day);
                        }
                    });
                }

                if (isset($req->status)) {
                    $query->where('status', $req->status);
                }

                $query->withCount([
                    'foodItems as active_food_items_count' => function ($query) {
                        $query->where('approved_status', 'approved');
                    },
                    'foodItems as unapproved_food_items_count' => function ($query) {
                        $query->where('approved_status', 'unapproved');
                    }
                ])->with([
                    'chefDocuments' => function ($q) {
                        $q->select('id', 'chef_id', 'document_field_id', 'field_value')
                            ->with('documentItemFields:id,document_item_list_id,field_name,type,mandatory');
                    }
                ])->orderBy('created_at', 'desc');
            }


            $data = $query->get();

            // For totalRecords, it should ideally be calculated before applying pagination to get accurate total count
            $totalRecords = empty($req->list) ? $query->count() : count($data);

            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage()); // It's better to use Log::error for exceptions
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }



    public function getAllContactData(Request $req)
    {
        try {
            $totalRecords = Contact::count();
            $skip = $req->page * 10;
            $data = Contact::with('chef')->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateContactDataStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "Please fill status",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Contact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteContactData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer|exists:contacts,id', // Ensure id is present, an integer, and exists in the contacts table
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400); // Return validation errors with appropriate status code
        }
        try {
            DB::beginTransaction(); // Start transaction for potential rollback if errors occur
            $contact = Contact::find($req->id);
            if (!$contact) {
                // Handle non-existent contact gracefully
                return response()->json(['message' => 'Contact not found.', 'success' => false], 404);
            }
            $contact->delete();
            DB::commit(); // Commit transaction if successful deletion

            return response()->json(['message' => 'Contact deleted successfully.', 'success' => true], 200);
        } catch (\Exception $th) {
            DB::rollBack(); // Rollback transaction if any errors occur
            Log::error($th->getMessage()); // Log the error message for debugging purposes
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // function sendMailToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "chef_id" => 'required',
    //         "subject" => 'required',
    //         "body" => 'required',
    //     ], [
    //         "chef_id.required" => "Please fill chef id",
    //         "subject.required" => "Please fill subject",
    //         "body.required" => "Please fill body of mail",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $chefDetail = Chef::select('email')->where('id', $req->chef_id)->first();
    //         $mail = [
    //             'subject' => $req->subject,
    //             'body' => $req->body,
    //             'chef' => $chefDetail,
    //         ];
    //         Log::info($mail);
    //         try {
    //             if (config('services.is_mail_enable')) {
    //                 Mail::to(trim($chefDetail->email))->send(new messageFromAdminToChef($mail));
    //             }
    //         } catch (\Exception $e) {
    //             Log::error($e);
    //         }
    //         $chefDetail->notify(new ChefEmailNotification($chefDetail));
    //         return response()->json(['message' => "Mail has been sent to chef's register email", 'success' => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
    //     }
    // }


    public function sendMailToChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "subject" => 'required',
            "body" => 'required',
        ], [
            "chef_id.required" => "Please fill chef id",
            "subject.required" => "Please fill subject",
            "body.required" => "Please fill body of mail",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $chefDetail = Chef::select('email', 'firstName', 'lastName')->where('id', $req->chef_id)->first();

            if (!$chefDetail) {
                return response()->json(['message' => 'Chef not found', 'success' => false], 404);
            }
            $mail = [
                'chef_name' => $chefDetail->firstName,
                'subject' => $req->subject,
                'body' => $req->body,
                // 'chef_email' => $chefDetail->email, // Pass only the email
            ];
            Log::info($mail);
            // Notify chef
            $chef = Chef::find($req->chef_id);
            $chef->notify(new ChefEmailNotification($mail));
            // Send email
            if (config('services.is_mail_enable')) {
                Mail::to(trim($chefDetail->email))->send(new MessageFromHomeplateTeamToChef($mail));
            }

            return response()->json(['message' => "Mail has been sent to chef's registered email", 'success' => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }


    function updateChangeRequestStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "Please fill status",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            RequestForUpdateDetails::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getAllRequestForChefReviewDeletion()
    {
        // try {
        //     $TotalRecords = ChefReviewDeleteRequest::where(['status' => 0])->count();
        //     $data = ChefReviewDeleteRequest::with(['user', 'chef', 'review'])->orderByDesc('created_at')->where(['status' => 0])->get();
        //     return response()->json(['data' => $data, 'TotalRecords' => $TotalRecords, 'success' => true], 200);
        // } catch (\Exception $th) {
        //     Log::info($th->getMessage());
        //     DB::rollback();
        //     return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        // }
        return response()->json(['data' => [], 'TotalRecords' => 0, 'success' => true], 200);
    }

    function updateStatusOfChefReviewDeleteRequest(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "review_id" => 'required',
            "id" => 'required',
            "status" => 'required',
        ], [
            "review_id.required" => "Please fill review id",
            "id.required" => "Please fill id",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chefReviewDeleteRequest = ChefReviewDeleteRequest::find($req->id);
            ChefReviewDeleteRequest::where('id', $req->id)->update(['status' => $req->status]);
            if ($req->status == 1) {
                ChefReview::where('id', $req->review_id)->update(['status' => 2]);
            }
            $UserController = new UserController;
            $UserController->updateChefrating($chefReviewDeleteRequest->chef_id);
            return response()->json(["message" => 'Updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getAllBlackListRequestByChef(Request $req)
    {
        try {
            $TotalRecords = RequestForUserBlacklistByChef::count();
            $data = RequestForUserBlacklistByChef::with(['user', 'chef', 'reviews'])->orderByDesc('created_at')->get();
            return response()->json(['data' => $data, 'TotalRecords' => $TotalRecords, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function blacklistUserOnChefRequest(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
            "chef_id" => 'required',
            "request_id" => 'required',
        ], [
            "user_id.required" => "Please fill user id",
            "chef_id.required" => "Please fill chef id",
            "request_id.required" => "Please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $chef = Chef::find($req->chef_id);

            $blaclistedArray = isset($chef->blacklistedUser) ? $chef->blacklistedUser : [];

            array_push($blaclistedArray, $req->user_id);

            Chef::where('id', $req->chef_id)->update(['blacklistedUser' => $blaclistedArray]);

            ChefReviewDeleteRequest::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->update(['status' => 1]);

            ChefReview::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->update(['status' => 2]);

            RequestForUserBlacklistByChef::where('id', $req->request_id)->update(['status' => 1]);

            $UserController = new UserController;
            $UserController->updateChefrating($req->chef_id);

            DB::commit();
            return response()->json(['message' => 'Blacklisted successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function unBlackListUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
            "chef_id" => 'required',
        ], [
            "user_id.required" => "Please fill user id",
            "chef_id.required" => "Please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = Chef::find($req->chef_id);

            $blaclistedArray = $chef->blacklistedUser;
            $user_id = $req->user_id;
            $newArray = array_filter($blaclistedArray, function ($value) use ($user_id) {
                return $value !== $user_id;
            });
            Chef::where('id', $req->chef_id)->update(['blacklistedUser' => $newArray]);

            return response()->json(['message' => 'Unblocked successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAllChefSuggestions(Request $req)
    {
        try {
            $totalRecords = ChefSuggestion::count();
            $skip = $req->page * 10;
            $data = ChefSuggestion::with('chef')->skip($skip)->take(10)->get();
            return response()->json(["data" => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function getAdminDshboardCount(Request $req)
    {
        try {
            $driver = Driver::count();
            $chef = Chef::count();
            $order = Order::count();
            $user = User::count();
            // $canelOrders = Order::where('')->count();

            return response()->json(["driver" => $driver, "chef" => $chef, "order" => $order, "user" => $user, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }


    public function getAllOrderDetails(Request $req)
    {
        try {
            $query = Order::query();
            if ($req->filter) {
                if ($req->from_date) {
                    $query->whereDate('created_at', '>=', $req->from_date);
                }
                if ($req->to_date) {
                    $query->whereDate('created_at', '<=', $req->to_date);
                }
                if ($req->user_id) {
                    $query->where('user_id', $req->user_id);
                }
                if ($req->chef_id) {
                    $query->whereHas('subOrders', function ($subQuery) use ($req) {
                        $subQuery->where('chef_id', $req->chef_id);
                    });
                }
            }
            $query->orderBy('created_at', 'desc');
            $query->with(['subOrders.orderItems.foodItem', 'subOrders.chefs']);
            $orders = $query->get();
            return response()->json(["orders" => $orders, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function getAdminOrderDetailsById(Request $req)
    {
        // Validation
        $validator = Validator::make($req->all(), [
            'id' => 'required|exists:orders,id',
            // Adjust validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $data = Order::where('id', $req->id)
                ->with('subOrders.orderItems.foodItem.chef')
                ->get(); // Use get() to retrieve multiple orders, not just the first one
            if ($data) {
                foreach ($data as $order) {
                    foreach ($order->subOrders as $subOrder) {
                        // Decode sub_order_tax_detail if it's stored as a JSON string
                        $subOrder->chef_commission_taxes = json_decode($subOrder->chef_commission_taxes, true);
                        $subOrder->driver_commission_taxes = json_decode($subOrder->driver_commission_taxes, true);
                        $subOrder->sub_order_tax_detail = json_decode($subOrder->sub_order_tax_detail, true);
                    }
                }
            }
            $trackDetails = [];

            foreach ($data as $order) {
                foreach ($order->subOrders as $subOrder) {
                    $trackId = $subOrder->track_id;
                    $trackDetail = OrderTrackDetails::where('track_id', $trackId)->get();
                    $trackDetails[$trackId] = $trackDetail;
                }
            }

            if ($data->isEmpty()) {
                return response()->json(["message" => "No orders found", "success" => true, 'data' => $data], 200);
            }

            return response()->json(["data" => $data, "trackDetails" => $trackDetails, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function getAllSubOrderDetails(Request $req)
    {
        try {
            $subOrders = SubOrders::with('orderItems.foodItem.chef')->with('Orders')->orderBy('created_at', 'desc')->get();
            $subOrders->transform(function ($subOrder) {
                if ($subOrder->chef_commission_taxes) {
                    $subOrder->chef_commission_taxes = json_decode($subOrder->chef_commission_taxes, true);
                }
                return $subOrder;
            });

            return response()->json(["subOrders" => $subOrders, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAdminSubOrderDetailsById(Request $req)
    {
        // Validation
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer',
            // Adjust validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $data = Order::where('id', $req->id)
                ->with('subOrders.orderItems.foodItem.chef')
                ->get(); // Use get() to retrieve multiple orders, not just the first one

            $trackDetails = [];

            foreach ($data as $order) {
                foreach ($order->subOrders as $subOrder) {
                    $trackId = $subOrder->track_id;
                    $trackDetail = OrderTrackDetails::where('track_id', $trackId)->get();
                    $trackDetails[$trackId] = $trackDetail;
                }
            }

            if ($data->isEmpty()) {
                return response()->json(["message" => "No orders found", "success" => true], 200);
            }

            return response()->json(["data" => $data, "trackDetails" => $trackDetails, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function addUpdateTemplateForFoodPackagingInstruction(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'template' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->id) {
                $update = [];
                if ($req->category) {
                    $update['category'] = $req->category;
                }
                if ($req->name) {
                    $update['name'] = $req->name;
                }
                if ($req->template) {
                    $update['template'] = $req->template;
                }
                PackingTemplates::where('id', $req->id)->update($update);
                return response()->json(['message' => 'Updated template successfully', 'success' => true], 200);
            } else {
                $newTemplate = new PackingTemplates();
                $newTemplate->category = $req->category;
                $newTemplate->name = $req->name;
                $newTemplate->template = $req->template;
                $newTemplate->save();
                return response()->json(['message' => 'Added template successfully', 'success' => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getTemplates(Request $req)
    {
        try {
            $query = PackingTemplates::query();
            if ($req->status) {
                $query->where('status', $req->status);
            }
            $data = $query->get();
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function updateTemplateStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required',
            'status' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            PackingTemplates::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => 'Updated template status successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteTemplate(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            PackingTemplates::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getChefAcceptedSuborder()
    {
        try {
            $subOrder = SubOrders::whereIn('status', ['3', 'approve'])->get();
            return response()->json(['message' => 'Deleted successfully', 'success' => true, 'data' => $subOrder], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
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

    public function getSubOrderByDriver(Request $req)
    {
        try {
            // Fetch sub-orders where driver_id is not null
            $subOrders = SubOrders::whereNotNull('driver_id')->get();

            return response()->json(['success' => true, 'message' => 'SubOrder fetched successfully', 'data' => $subOrders], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong.'], 500);
        }
    }

    public function storeChefChecklist(Request $req)
    {
        // Step 1: Validate only chef_id initially
        $validator = Validator::make($req->all(), [
            'chef_id' => 'required|exists:chefs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                "message" => "Validation failed",
                "errors" => $validator->errors()->first(),
                "success" => false
            ], 400);
        }

        try {
            $chef = Chef::find($req->chef_id);

            if (!$chef) {
                Log::error("Chef not found with ID: " . $req->chef_id);
                return response()->json(['success' => false, 'message' => 'Chef not found'], 404);
            }

            // Step 2: Define allowed checklist fields
            $allowedKeys = [
                'is_personal_details_completed',
                'is_special_benefit_document_completed',
                'is_document_details_completed',
                'is_fhc_document_completed',
                'is_rrc_certificate_document_completed',
                'is_bank_detail_completed',
                'is_social_detail_completed',
                'is_kitchen_detail_completed',
                'is_tax_document_completed'
            ];

            // Step 3: Log incoming data
            Log::info('Incoming request data', $req->all());

            // Step 4: Filter and validate checklist fields
            $updates = collect($req->only($allowedKeys))->map(function ($val, $key) use ($allowedKeys) {
                return in_array($val, ['0', '1', 0, 1], true) ? (int) $val : null;
            })->filter(fn($val) => $val !== null)->toArray();

            // Step 5: Optional validation - if invalid values exist
            foreach ($req->only($allowedKeys) as $key => $val) {
                if (!in_array($val, ['0', '1', 0, 1], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Invalid value for $key. Allowed: 0 or 1."
                    ], 422);
                }
            }

            // Step 6: If nothing to update, return early
            if (empty($updates)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No valid fields to update'
                ], 422);
            }

            // Step 7: Log current and updated data
            Log::info('Data to update', $updates);
            Log::info('Before update', $chef->only(array_keys($updates)));

            // Step 8: Update and log after
            $chef->update($updates);
            $chef->refresh();

            Log::info('After update', $chef->only(array_keys($updates)));

            return response()->json([
                'success' => true,
                'message' => 'Chef checklist updated successfully',
                'data' => $chef
            ], 200);
        } catch (\Exception $e) {
            Log::error('Exception occurred while updating chef checklist', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong.',
                'error' => $e->getMessage()
            ], 500);
        }
    }



    public function storeDriverChecklist(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'driver_id' => 'required|exists:drivers,id',
            'is_personal_details_completed' => 'sometimes|required|in:0,1',
            'is_driving_license_document_completed' => 'sometimes|required|in:0,1',
            'is_address_proof_document_completed' => 'sometimes|required|in:0,1',
            'is_tax_document_completed' => 'sometimes|required|in:0,1',
            'is_bank_document_detail' => 'sometimes|required|in:0,1'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $driverChecklist = Driver::findOrFail($req->driver_id);
            if (!$driverChecklist) {
                return response()->json(['success' => false, 'message' => 'Driver not found'], 404);
            }
            $driverChecklist->update($req->only([
                'is_personal_details_completed',
                'is_driving_license_document_completed',
                'is_address_proof_document_completed',
                'is_tax_document_completed',
                'is_bank_document_detail'
            ]));

            return response()->json(['success' => true, 'message' => 'Driver checklist updated successfully', 'data' => $driverChecklist], 200);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Oops! Something went wrong.'], 500);
        }
    }

    function ChefReviewInAdmin(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                "user_id" => 'required|exists:users,id',
                "chef_id" => 'required|exists:chefs,id',
                "star_rating" => "required",
                "message" => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" =>  $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $reviewExist = ChefReview::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id, 'status' => 1])->first();
            if ($reviewExist) {
                ChefReview::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->update(['star_rating' => $req->star_rating, 'message' => $req->message]);
            } else {
                $newReview = new ChefReview();
                $newReview->user_id = $req->user_id;
                $newReview->chef_id = $req->chef_id;
                $newReview->star_rating = $req->star_rating;
                $newReview->message = $req->message;
                $newReview->save();
            }
            $reviewDetails = ChefReview::orderBy('created_at', 'desc')->with(['user', 'chef'])->where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->first();
            $reviewDetails['date'] = Carbon::now();
            $chef = Chef::find($req->chef_id);
            $chef->notify(new NewChefReviewNotification($reviewDetails));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new NewReviewNotification($reviewDetails));
            }
            $this->updateChefrating($req->chef_id);
            return response()->json(['message' => "Submitted successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => $th->getMessage() . 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // This function is used in ChefReviewInAdmin Founction
    function updateChefrating($chef_id)
    {
        $allReview = ChefReview::select('star_rating')->where(['chef_id' => $chef_id, 'status' => 1])->get();
        $totalNoReview = ChefReview::where(['chef_id' => $chef_id, 'status' => 1])->count();
        $totalStars = 0;
        foreach ($allReview as $value) {
            $totalStars = $totalStars + $value['star_rating'];
        }
        $rating = $totalStars / $totalNoReview;
        Chef::where('id', $chef_id)->update(['rating' => $rating]);
    }


    public function getChecklist(Request $request)
    {
        $chef_id = $request->query('chef_id');

        if (!$chef_id) {
            return response()->json([
                'success' => false,
                'message' => 'Chef ID is required.'
            ], 400);
        }

        $checklistFields = [
            'is_personal_details_completed',
            'is_special_benefit_document_completed',
            'is_document_details_completed',
            'is_fhc_document_completed',
            'is_rrc_certificate_document_completed',
            'is_bank_detail_completed',
            'is_social_detail_completed',
            'is_kitchen_detail_completed',
            'is_tax_document_completed',
        ];

        $chef = Chef::select(array_merge(['id'], $checklistFields))->find($chef_id);

        if (!$chef) {
            return response()->json([
                'success' => false,
                'message' => 'Chef not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $chef
        ]);
    }
}
