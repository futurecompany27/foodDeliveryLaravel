<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Mail\MessageFromAdminToChef;
use App\Models\Admin;
use App\Models\Adminsetting;
use App\Models\Allergy;
use App\Models\chef;
use App\Models\ChefReview;
use App\Models\chefReviewDeleteRequest;
use App\Models\ChefSuggestion;
use App\Models\Contact;
use App\Models\Dietary;
use App\Models\Driver;
use App\Models\FoodCategory;
use App\Models\HeatingInstruction;
use App\Models\Ingredient;
use App\Models\Order;
use App\Models\RequestForUpdateDetails;
use App\Models\RequestForUserBlacklistByChef;
use App\Models\Sitesetting;
use App\Models\SubOrders;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\ImageManagerStatic as Image;
// use Intervention\Image\Image; //Intervention Image
use Illuminate\Support\Facades\File;

class AdminController extends Controller
{
    function adminRegistration(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "name" => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8',
            "status" => 'required',
        ], [
            "name.required" => "please fill name",
            "email.required" => "please fill email",
            "password.required" => "please fill password",
            "status.required" => "please fill status",
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
            $admin->name = $req->name;
            $admin->email = $req->email;
            $admin->password = Hash::make($req->password);
            $admin->status = $req->status;
            $admin->save();
            DB::commit();
            return response()->json(["message" => "Registered successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function adminLogin(Request $req)
    {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:8'
        ];
        $validate = Validator::make($req->all(), $rules);
        if ($validate->fails()) {
            return response()->json(["message" => $validate->errors(), "success" => false], 400);
        }
        try {
            $adminDetail = Admin::where("email", $req->email)->first();
            if ($adminDetail) {
                $adminDetail->makeVisible('password');
                if (Hash::check($req->password, $adminDetail['password'])) {
                    return response()->json(["message" => "Logged In successfully", 'admin_id' => $adminDetail->id, "success" => true], 200);
                } else {
                    return response()->json(['message' => 'Invalid Credentials!', 'success' => false], 500);
                }
            } else {
                return response()->json(['message' => 'Invalid Credentials!', 'success' => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to Login again !', 'success' => false], 500);
        }
    }

    public function updateSiteSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteSiteSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Sitesetting::where('id', $req->id)->first();
            Sitesetting::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function addAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "default_comm" => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            'refugee_comm' => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            'singlemom_comm' => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            "lostjob_comm" => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            'student_comm' => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            'food_default_comm' => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
            "radius" => 'required|regex:^(?:[0-4]?\d\d|500|\d+(\.\d+)?)$',
        ], [
            "default_comm.required" => "please fill default_comm",
            "default_comm.regex" => "please enter valid default_comm",
            "refugee_comm.required" => "please fill refugee_comm",
            "refugee_comm.regex" => "please enter valid refugee_comm",
            "singlemom_comm.required" => "please fill singlemom_comm",
            "singlemom_comm.regex" => "please enter valid singlemom_comm",
            "lostjob_comm.required" => "please fill lostjob_comm",
            "lostjob_comm.regex" => "please enter valid lostjob_comm",
            "student_comm.required" => "please fill student_comm",
            "student_comm.regex" => "please enter valid student_comm",
            "food_default_comm.required" => "please fill food_default_comm",
            "food_default_comm.regex" => "please enter valid food_default_comm",
            "radius.required" => "please fill radius",
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

            $adminSetting->save();
            return response()->json(["message" => "Submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            // Check if the value exceeds 500
            if ($req->all() > 500) {
                return response()->json(["message" => "The input value must be lower than 500.", "success" => false], 400);
            }
            $data = Adminsetting::where('id', $req->id)->first();
            $updateData = $req->all();

            Adminsetting::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteAdminSettings(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Adminsetting::where('id', $req->id)->first();
            Adminsetting::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getAdminSettings(Request $req)
    {
        try {
            $totalRecords = Adminsetting::count();
            $skip = $req->page * 10;
            $data = Adminsetting::skip($skip)->take(10)->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function addFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "category" => 'required',
            "commission" => 'nullable|integer|between:1,100',
            "image" => 'required',
        ], [
            "category.required" => "please fill category",
            "image.required" => "please fill image",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if ($req->hasFile('image')) {
            $file = $req->file('image')->store("admin/food_category/", "public");
            $filename = asset('storage/' . $file);
        }
        try {
            $foodcategory = new FoodCategory();
            $foodcategory->category = $req->category;
            // Check if 'commission' is provided; if not, set a default value
            $foodcategory->commission = $req->commission ?? 10;

            $foodcategory->image = $filename;
            $foodcategory->save();
            return response()->json(["message" => "Submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
                $images = $data->image;
                str_replace(env('filePath'), '', $images);
                if (file_exists(str_replace(env('filePath'), '', $images))) {
                    unlink(str_replace(env('filePath'), '', $images));
                }
                $file = $req->file('image')->store("admin/food_category/", "public");
                $filename = asset('storage/' . $file);
                $updateData['image'] = $filename;
            }
            FoodCategory::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteFoodTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    public function addAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "image" => 'required',
            "small_description" => 'required',
            "allergy_name" => 'required',
        ], [
            "image.required" => "please fill image",
            "small_description.required" => "please fill small_description",
            "allergy_name.required" => "please fill allergy_name",
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

                $image_name = strtolower($req->allergy_name);
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                $big_img = Image::make($req->file('image'))
                    ->resize(200, 200)
                    ->save('storage/admin/allergen_icons/' . $name_gen);

                $filename = asset("storage/admin/allergen_icons/" . $name_gen);
            }
            Allergy::insert([
                'image' => $filename,
                'small_description' => $req->small_description,
                'allergy_name' => strtolower($req->allergy_name),
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(["message" => "added successfully", "success" => true], 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if (!File::exists("storage/admin/allergen_icons/")) {
            File::makeDirectory("storage/admin/allergen_icons/", $mode = 0777, true, true);
        }
        try {
            $data = Allergy::where('id', $req->id)->first();
            $updateData = [];
            if ($req->hasFile('image')) {
                $images = $data->image;
                str_replace(env('filePath'), '', $images);
                if (file_exists(str_replace(env('filePath'), '', $images))) {
                    unlink(str_replace(env('filePath'), '', $images));
                }
                if ($req->file('image') && isset($req->image)) {

                    $big_image = $req->file('image');
                    $image_name = strtolower($req->allergy_name);
                    $new_name = str_replace(" ", "", $image_name);
                    $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                    $big_img = Image::make($req->file('image'))
                        ->resize(200, 200)
                        ->save('storage/admin/allergen_icons/' . $name_gen);
                    $filename = asset("storage/admin/allergen_icons/" . $name_gen);
                    $updateData['image'] = $filename;
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteAllergies(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    public function addDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "diet_name" => 'required',
            "small_description" => 'required',
            "image" => 'required',
        ], [
            "diet_name.required" => "please fill diet_name",
            "small_description.required" => "please fill small_description",
            "image.required" => "please fill image",
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

            if ($req->file('image') && isset($req->image)) {

                $big_image = $req->file('image');

                $image_name = strtolower($req->diet_name);
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                $big_img = Image::make($req->file('image'))
                    ->resize(200, 200)
                    ->save('storage/admin/dietaries_icons/' . $name_gen);

                $filename = asset("storage/admin/dietaries_icons/" . $name_gen);
            }
            Dietary::insert([
                'diet_name' => strtolower($req->diet_name),
                'small_description' => $req->small_description,
                'image' => $filename,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(["message" => "added successfully", "success" => true], 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
                $images = $data->image;
                str_replace(env('filePath'), '', $images);
                if (file_exists(str_replace(env('filePath'), '', $images))) {
                    unlink(str_replace(env('filePath'), '', $images));
                }
                if ($req->file('image') && isset($req->image)) {
                    $big_image = $req->file('image');
                    $image_name = strtolower($req->diet_name);
                    $new_name = str_replace(" ", "", $image_name);
                    $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                    $big_img = Image::make($req->file('image'))
                        ->resize(200, 200)
                        ->save('storage/admin/dietaries_icons/' . $name_gen);
                    $filename = asset("storage/admin/dietaries_icons/" . $name_gen);
                    $updateData['image'] = $filename;
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteDietaries(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    public function addHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "title" => 'required',
            "description" => 'required',
        ], [
            "title.required" => "please fill title",
            "description.required" => "please fill description",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = HeatingInstruction::where('id', $req->id)->first();
            $updateData = $req->all();
            HeatingInstruction::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteHeatingInstructions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = HeatingInstruction::where('id', $req->id)->first();
            HeatingInstruction::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateHeatingInstructionsStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function addIngredients(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "ing_name" => 'required',
        ], [
            "ing_name.required" => "please fill ing_name",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function updateIngredient(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Ingredient::where('id', $req->id)->first();
            $updateData = $req->all();
            Ingredient::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteIngredient(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $data = Ingredient::where('id', $req->id)->first();
            Ingredient::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateIngredientStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    // public function sendMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "chef_id" => 'required',
    //         "subject" => 'required',
    //         "message" => 'required',
    //     ], [
    //         "chef_id.required" => "please fill chef_id",
    //         "subject.required" => "please fill subject",
    //         "message.required" => "please fill message",
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
    //     } catch (\Throwable $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
    //     }
    // }

    // public function updateMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //     ], [
    //         "id.required" => "please fill id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $updateData = $req->all();
    //         Contact::where('id', $req->id)->update($updateData);
    //         return response()->json(['message' => "Updated Successfully", "success" => true], 200);
    //     } catch (\Throwable $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
    //     }
    // }

    // public function deleteMessageToChef(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //     ], [
    //         "id.required" => "please fill id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         Contact::where('id', $req->id)->delete();
    //         return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
    //     } catch (\Throwable $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
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
    //     } catch (\Throwable $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
    //     }
    // }

    function getAllUsers(Request $req)
    {
        try {
            $totalRecords = User::count();
            $skip = $req->page * 10;
            $data = User::orderBy('created_at', 'desc')->skip($skip)->take(10)->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
                'success' => true
            ], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function getAllChefs(Request $req)
    {
        try {
            $totalRecords = chef::count();

            $query = chef::orderBy('created_at', 'desc')->withCount([
                'foodItems as active_food_items_count' => function ($query) {
                    $query->where('approved_status', 'active');
                },
                'foodItems as pending_food_items_count' => function ($query) {
                    $query->where('approved_status', 'pending');
                }
            ])->with([
                'chefDocuments' => fn ($q) => $q->select('id', 'chef_id', 'document_field_id', 'field_value')->with([
                    'documentItemFields' => fn ($qr) => $qr->select('id', 'document_item_list_id', 'field_name', 'type', 'mandatory')
                ])
            ]);

            $skip = $req->page * 10;

            $data = $query->skip($skip)->take(10)->get();

            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    public function getAllContactData(Request $req)
    {
        try {
            $totalRecords = Contact::count();
            $skip = $req->page * 10;
            $data = Contact::with('chef')->skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateContactDataStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Contact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function sendMailToChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "subject" => 'required',
            "body" => 'required',
        ], [
            "chef_id.required" => "please fill chef id",
            "subject.required" => "please fill subject",
            "body.required" => "please fill body of mail",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chefDetail = chef::select('email')->where('id', $req->chef_id)->first();
            $mail = ['subject' => $req->subject, 'body' => $req->body];
            Mail::to(trim($chefDetail->email))->send(new messageFromAdminToChef($mail));
            return response()->json(['message' => "Mail has been sent to chef's register email", 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again after sometime !', 'success' => false], 500);
        }
    }

    function updateChnageRequestStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            RequestForUpdateDetails::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getAllRequestForChefReviewDeletion()
    {
        try {
            $TotalRecords = chefReviewDeleteRequest::where(['status' => 0])->count();
            $data = chefReviewDeleteRequest::with(['user', 'chef', 'review'])->orderByDesc('created_at')->where(['status' => 0])->get();
            return response()->json(['data' => $data, 'TotalRecords' => $TotalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function updateStatusOfChefReviewDeleteRequest(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "review_id" => 'required',
            "id" => 'required',
            "status" => 'required',
        ], [
            "review_id.required" => "please fill review id",
            "id.required" => "please fill id",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chefReviewDeleteRequest = chefReviewDeleteRequest::find($req->id);
            chefReviewDeleteRequest::where('id', $req->id)->update(['status' => $req->status]);
            if ($req->status == 1) {
                ChefReview::where('id', $req->review_id)->update(['status' => 2]);
            }
            $UserController = new UserController;
            $UserController->updateChefrating($chefReviewDeleteRequest->chef_id);
            return response()->json(["message" => 'Updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getAllBlackListRequestByChef(Request $req)
    {
        try {
            $TotalRecords = RequestForUserBlacklistByChef::count();
            $data = RequestForUserBlacklistByChef::with(['user', 'chef', 'reviews'])->orderByDesc('created_at')->get();
            return response()->json(['data' => $data, 'TotalRecords' => $TotalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function blacklistUserOnChefRequest(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
            "chef_id" => 'required',
            "request_id" => 'required',
        ], [
            "user_id.required" => "please fill user id",
            "chef_id.required" => "please fill chef id",
            "request_id.required" => "please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $chef = chef::find($req->chef_id);

            $blaclistedArray = isset($chef->blacklistedUser) ? $chef->blacklistedUser : [];

            array_push($blaclistedArray, $req->user_id);

            chef::where('id', $req->chef_id)->update(['blacklistedUser' => $blaclistedArray]);

            chefReviewDeleteRequest::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->update(['status' => 1]);

            ChefReview::where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->update(['status' => 2]);

            RequestForUserBlacklistByChef::where('id', $req->request_id)->update(['status' => 1]);

            $UserController = new UserController;
            $UserController->updateChefrating($req->chef_id);

            DB::commit();
            return response()->json(['message' => 'blacklisted successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function unBlackListUser(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
            "chef_id" => 'required',
        ], [
            "user_id.required" => "please fill user id",
            "chef_id.required" => "please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);

            $blaclistedArray = $chef->blacklistedUser;
            $user_id = $req->user_id;
            $newArray = array_filter($blaclistedArray, function ($value) use ($user_id) {
                return $value !== $user_id;
            });
            chef::where('id', $req->chef_id)->update(['blacklistedUser' => $newArray]);

            return response()->json(['message' => 'Unblocked successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getAllChefSuggestions()
    {
        try {
            $data = ChefSuggestion::all();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    public function getAdminDshboardCount(Request $req)
    {
        try {
            $driver = Driver::count();
            $chef = chef::count();
            $order = Order::count();
            $user = User::count();
            // $canelOrders = Order::where('')->count();

            return response()->json(["driver" => $driver, "chef" => $chef, "order" => $order, "user" => $user, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    public function getAllOrderDetails(Request $req)
    {
        try {
            $orders = Order::with('subOrders.orderItems.foodItem.chef')
                ->get();
            return response()->json(["orders" => $orders, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    public function getAdminOrderDetailsById(Request $req)
    {
        // Validation
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer', // Adjust validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $orders = Order::where('id', $req->id)
                ->with('subOrders.orderItems.foodItem.chef')
                ->get();
            return response()->json(["orders" => $orders, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }


    public function getAllSubOrderDetails(Request $req)
    {
        try {
            $subOrders = SubOrders::with('orderItems.foodItem')->with('Orders')->get();
            return response()->json(["subOrders" => $subOrders, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    public function getAdminSubOrderDetailsById(Request $req)
    {
        // Validation
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer', // Adjust validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $subOrders = SubOrders::where('id', $req->id)->with('orderItems.foodItem')->with('Orders')->get();
            return response()->json(["subOrders" => $subOrders, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }
}
