<?php

namespace App\Http\Controllers\chefs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Mail\HomeshefChefChangeEmailVerificationLink;
use App\Mail\HomeshefChefEmailVerification;
use App\Mail\HomeshefChefEmailVerifiedSuccessfully;
use App\Mail\HomeshefChefStatusChangeMail;
use App\Mail\HomeshefFoodItemStatusChange;
use App\Models\Admin;
use App\Models\Allergy;
use App\Models\chef;
use App\Models\ChefAlternativeContact;
use App\Models\ChefDocument;
use App\Models\ChefProfileReviewByAdmin;
use App\Models\ChefReview;
use App\Models\chefReviewDeleteRequest;
use App\Models\ChefSuggestion;
use App\Models\FoodItem;
use App\Models\RequestForUpdateDetails;
use App\Models\RequestForUserBlacklistByChef;
use App\Models\ScheduleCall;
use App\Models\ShefRegisterationRequest;
use App\Models\User;
use App\Models\Contact;
use App\Models\Dietary;
use App\Models\Driver;
use App\Models\Ingredient;
use App\Models\Kitchentype;
use App\Models\Order;
use App\Models\OrderTrackDetails;
use App\Notifications\admin\ChefRegisterationRequest;
use App\Notifications\admin\RequestQueryNotification;
use App\Notifications\Chef\ChefContactUsNotification;
use App\Notifications\Chef\ChefFoodItemNotification;
use App\Notifications\Chef\ChefScheduleCallNotification;
use App\Notifications\Chef\ChefStatusUpdateNotification;
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
use App\Notifications\chef\foodItemstatusChangeMail;
use Illuminate\Support\Facades\Validator;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\File;

class ChefController extends Controller
{
    function ChefRegisteration(Request $req)
    {
        try {
            DB::beginTransaction();
            $checkPinCode = Pincode::where(['pincode' => substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3), 'status' => 1])->first();
            if (!$checkPinCode) {
                return response()->json(['message' => 'we are not offering our services in this region', 'ServiceNotAvailable' => true, 'success' => false], 200);
            }

            $chefExist = chef::where("email", $req->email)->first();
            if ($chefExist) {
                return response()->json(['message' => "This email is already register please use another email!", "success" => false], 400);
            }
            $chefExist = chef::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefExist) {
                return response()->json(['message' => "This mobileno is already register please use another mobileno!", "success" => false], 400);
            }
            $chef = new chef();
            $chef->first_name = ucfirst($req->first_name);
            $chef->last_name = ucfirst($req->last_name);
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
            $chefDetail = chef::find($chef->id);

            $userExist = User::where("email", $req->email)->first();
            if (!$userExist) {
                // creating new instance of user Controller so that we can access function of userController 
                $UserController = new UserController;
                $request = new Request();
                $request->merge([
                    "fullname" => (ucfirst($req->first_name) . " " . ucfirst($req->last_name)),
                    "mobile" => $req->mobile,
                    "email" => $req->email,
                    "password" => $req->password
                ]);
                $UserController->UserRegisteration($request);
            }

            Mail::to(trim($req->email))->send(new HomeshefChefEmailVerification($chefDetail));
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new ChefRegisterationNotification($chefDetail));
            }

            DB::commit();
            return response()->json(['message' => 'Register successfully!', "data" => $chefDetail, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
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
            "postal_code" => 'required',
            "latitude" => "required",
            "longitude" => "required",
            "city" => "required",
            "state" => "required"
        ], [
            "chef_id.required" => "please mention chef_id",
            "first_name.required" => "please fill firstname",
            "last_name.required" => "please fill lastname",
            "type.required" => "please select type",
            "sub_type.required" => "please select sub-type",
            "address_line1.required" => "please fill addressLine1",
            "postal_code" => "please fill postal code",
            "latitude" => "please fill latitude",
            "longitude" => "please fill longitude",
            "city" => "please fill city",
            "state" => "please fill state"
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $update = [
                "first_name" => ucfirst($req->first_name),
                "last_name" => ucfirst($req->last_name),
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
                $update['profile_pic'] = $profile;
            }

            chef::where('id', $req->chef_id)->update($update);
            return response()->json(["message" => "profile updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
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
            return response()->json(['message' => 'please fill all the fields', 'success' => false], 400);
        }
        $chefDetail = chef::where("email", $req->email)->first();

        if ($chefDetail) {
            $chefDetail->makeVisible('password');
            if (Hash::check($req->password, $chefDetail['password'])) {
                $chefDetail->makeHidden('password');
                return response()->json(['message' => 'Logged in successfully!', 'data' => $chefDetail, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 500);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 500);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = chef::whereId($req->chef_id)->with([
                'chefDocuments' => fn($q) => $q->select('id', 'chef_id', 'document_field_id', 'field_value')->with([
                    'documentItemFields' => fn($qr) => $qr->select('id', 'document_item_list_id', 'field_name', 'type', 'mandatory')
                ])
            ])->first();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateChefDetailsStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill chef id",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            chef::where('id', $req->id)->update(['status' => $req->status]);
            $chefDetail = chef::find($req->id);
            $chefDetail->notify(new ChefStatusUpdateNotification($chefDetail));
            Mail::to(trim($chefDetail->email))->send(new HomeshefChefStatusChangeMail($chefDetail));
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function updateChefPrimaryEmail(Request $req)
    {
        if (!$req->chef_id || !$req->new_email) {
            return response()->json(["message" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $chefDetails = chef::find($req->chef_id);
            if ($chefDetails->email == $req->new_email) {
                return response()->json(['message' => 'Trying to use an existing primary email. Please use another email.', "success" => false], 500);
            }

            $isNewEmailAlreadyRegistered = chef::where('email', $req->new_email)->first();
            if (!$isNewEmailAlreadyRegistered) {
                $chefDetails->email = trim($req->new_email);
                $chefDetails->is_email_verified = 0;
                $chefDetails->email_verified_at = null;
                $chefDetails->status = 0;
                $chefDetails->save();
                Mail::to(trim($req->new_email))->send(new HomeshefChefChangeEmailVerificationLink($chefDetails));
                return response()->json(['message' => "Updated sucessfully", "success" => true], 200);
            } else {
                return response()->json(["error" => "This email is already registerd with Homeshef", "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateSocialMediaLinks(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateBankDetails(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
            "bank_name" => 'required',
            "account_number" => 'required',
            "transit_number" => 'required',
            "institution_number" => 'required',
        ], [
            "chef_id.required" => "please mention chef_id",
            "bank_name.required" => "please fill bank_name",
            "account_number.required" => "please fill account_number",
            "transit_number.required" => "please select transit_number",
            "institution_number.required" => "please select institution_number",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);
            $chef->bank_name = $req->bank_name;
            $chef->account_number = $req->account_number;
            $chef->transit_number = $req->transit_number;
            $chef->institution_number = $req->institution_number;
            $chef->status = 0;
            $chef->save();
            return response()->json(['message' => "updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateDocuments(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $path = 'chef/' . $req->chef_id;

            $chef = chef::find($req->chef_id);

            // store address proof 
            if (isset($req->address_proof) && $req->hasFile('address_proof_path')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->address_proof_path))) {
                    unlink(str_replace(env('filePath'), '', $chef->address_proof_path));
                }
                $storedPath = $req->file('address_proof_path')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["address_proof_path" => asset('storage/' . $storedPath), "address_proof" => $req->address_proof, 'status' => 0]);
            }

            // store ID proof 1
            if (isset($req->id_proof_1) && $req->hasFile('id_proof_path1')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->id_proof_path1))) {
                    unlink(str_replace(env('filePath'), '', $chef->id_proof_path1));
                }
                $storedPath = $req->file('id_proof_path1')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["id_proof_1" => $req->id_proof_1, "id_proof_path1" => asset('storage/' . $storedPath), 'status' => 0]);
            }

            // store ID proof 2
            if (isset($req->id_proof_2) && $req->hasFile('id_proof_path2')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->id_proof_path1))) {
                    unlink(str_replace(env('filePath'), '', $chef->id_proof_path1));
                }
                $storedPath = $req->file('id_proof_path2')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["id_proof_2" => $req->id_proof_2, "id_proof_path2" => asset('storage/' . $storedPath), 'status' => 0]);
            }

            // Additional fields which has values in string
            if (isset($req->typeTextData)) {
                $textTypeData = $req->typeTextData;
                foreach ($textTypeData as $value) {
                    $data = json_decode($value);
                    if (isset($data->value) && $data->value != "") {
                        ChefDocument::updateOrCreate(
                            [
                                "chef_id" => $req->chef_id,
                                "document_field_id" => $data->id
                            ],
                            [
                                "field_value" => $data->value
                            ]
                        );
                        chef::where("id", $req->chef_id)->update(['status' => 0]);
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
                                "chef_id" => $req->chef_id,
                                "document_field_id" => $value
                            ],
                            [
                                "field_value" => asset('storage/' . $storedPath)
                            ]
                        );
                        chef::where("id", $req->chef_id)->update(['status' => 0]);
                    }
                }
            }

            DB::commit();
            return response()->json(['message' => "updated successfully", 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateKitchen(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $path = 'chef/' . $req->chef_id;
            $chef = chef::find($req->chef_id);

            if ($req->hasFile('chef_banner_image')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->chef_banner_image))) {
                    unlink(str_replace(env('filePath'), '', $chef->chef_banner_image));
                }
                $storedPath = $req->file('chef_banner_image')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["chef_banner_image" => asset('storage/' . $storedPath)]);
            }

            if ($req->hasFile('chef_card_image')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->chef_card_image))) {
                    unlink(str_replace(env('filePath'), '', $chef->chef_card_image));
                }
                $storedPath = $req->file('chef_card_image')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["chef_card_image" => asset('storage/' . $storedPath)]);
            }

            $update = [
                "kitchen_types" => $req->kitchen_types,
                "about_kitchen" => $req->about_kitchen,
                "kitchen_name" => $req->kitchen_name,
                'status' => 0
            ];
            chef::where('id', $req->chef_id)->update($update);
            DB::commit();
            return response()->json(["message" => "updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function updateSpecialBenifits(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $path = 'chef/' . $req->chef_id;
            $chef = chef::find($req->chef_id);
            if ($req->hasFile('are_you_a_file_path')) {
                if (file_exists(str_replace(env('filePath'), '', $chef->are_you_a_file_path))) {
                    unlink(str_replace(env('filePath'), '', $chef->are_you_a_file_path));
                }
                $storedPath = $req->file('are_you_a_file_path')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["are_you_a_file_path" => asset('storage/' . $storedPath), "are_you_a" => $req->are_you_a, 'status' => 0]);
                return response()->json(["message" => "updated successfully", "success" => true], 200);
            } else {
                return response()->json(["message" => "please upload proof of " . $req->are_you_a, "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function AddContactData(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "please fill all the required fields ", "success" => false], 400);
        }
        try {
            $contact = new contact();
            $contact->chef_id = $req->chef_id;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();
            $contactUs = contact::orderBy('created_at', 'desc')->where('chef_id', $req->chef_id)->with('chef')->first();
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new ChefContactUsNotification($contactUs));
            }
            return response()->json(['message' => 'Submitted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
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
            return response()->json(["message" => 'Call has been scheduled successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function chefAddNewOrUpdateFoodItem(Request $req)
    {
        try {
            if ($req->food_id && $req->chef_id) {

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
                    $directoryPath = 'storage/foodItem/';
                    $directoryPathThumbnail = 'storage/foodItem/thumbnail/';
                    if (file_exists(str_replace(env('filePath'), '', $foodData->dishImage))) {
                        unlink(str_replace(env('filePath'), '', $foodData->dishImage));
                        $foodData->dishImage = '';
                    }

                    if (file_exists(str_replace(env('filePath'), '', $foodData->dishImageThumbnail))) {
                        unlink(str_replace(env('filePath'), '', $foodData->dishImageThumbnail));
                        $foodData->dishImageThumbnail = '';
                    }

                    $image = Image::make($req->file('foodImage'));
                    $name_gen = hexdec(uniqid()) . '.' . $req->file('foodImage')->getClientOriginalExtension();
                    $OGfilePath = $directoryPath . $name_gen;
                    $image->fit(800, 800)->save($OGfilePath);
                    $filename_thumb = $directoryPathThumbnail . $name_gen;
                    $image->fit(200, 200)->save($filename_thumb);

                    $foodData->dishImage = asset($OGfilePath);
                    $foodData->dishImageThumbnail = asset($filename_thumb);
                }
                $foodData->save();
                $chefDetail = chef::find($req->chef_id);
                $chefDetail['flag'] = 2;
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new ChefFoodItemNotification($chefDetail));
                }
                return response()->json(['message' => 'updated successfully', 'success' => true], 200);
            } else {
                $validator = Validator::make(
                    $req->all(),
                    [
                        'dish_name' => 'required',
                        'description' => 'required',
                        'foodImage' => 'required|image',
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
                    ],
                    [
                        'dish_name.required' => 'Please mention dish name',
                        'description.required' => 'Please add dish discription',
                        'foodImage.required' => 'please add dish image',
                        'foodImage.image' => 'please select image file only',
                        'regularDishAvailabilty.required' => 'please mention regularity of the dish',
                        'foodAvailibiltyOnWeekdays.required' => 'please mention weekdays availablity of the food',
                        'orderLimit.required' => 'please mention order limit',
                        'orderLimit.numeric' => 'order limit must be in number only',
                        'foodTypeId.required' => 'please select food type',
                        'spicyLevel.required' => 'please select spicy level of the food',
                        'heating_instruction_id.required' => 'please select heating instruction option',
                        'heating_instruction_description.required' => 'please enter food heading instruction',
                        'package.required' => 'please select package type',
                        'size.required' => 'Please enter package size',
                        'expiresIn.required' => 'Please mention the expirey period of the food',
                        'serving_unit.required' => 'Please mention serving unit',
                        'serving_person.required' => 'Please mention the food sufficency',
                        'price.required' => 'please mention the price of the food',
                    ]
                );

                if ($validator->fails()) {
                    return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
                }

                $OGfilePath = "";
                $filename_thumb = "";
                if ($req->hasFile('foodImage')) {
                    $directoryPath = 'storage/foodItem/';
                    $directoryPathThumbnail = 'storage/foodItem/thumbnail/';
                    if (!file_exists($directoryPath)) {
                        mkdir($directoryPath, 0755, true);
                    }
                    if (!file_exists($directoryPathThumbnail)) {
                        mkdir($directoryPathThumbnail, 0755, true);
                    }
                    $image = Image::make($req->file('foodImage'));
                    $name_gen = hexdec(uniqid()) . '.' . $req->file('foodImage')->getClientOriginalExtension();
                    $OGfilePath = $directoryPath . $name_gen;
                    $image->fit(800, 800)->save($OGfilePath);
                    $filename_thumb = $directoryPathThumbnail . $name_gen;
                    $image->fit(200, 200)->save($filename_thumb);
                }

                DB::beginTransaction();
                $foodItem = new FoodItem();
                $foodItem->chef_id = $req->chef_id;
                $foodItem->dish_name = $req->dish_name;
                $foodItem->description = $req->description;
                $foodItem->dishImage = asset($OGfilePath);
                $foodItem->dishImageThumbnail = asset($filename_thumb);
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
                $foodItem->package = $req->package;
                $foodItem->size = $req->size;
                $foodItem->expiresIn = $req->expiresIn;
                $foodItem->serving_unit = $req->serving_unit;
                $foodItem->serving_person = $req->serving_person;
                $foodItem->price = $req->price;
                $foodItem->comments = $req->comments;
                $foodItem->save();
                DB::commit();
                $chefDetail = chef::find($req->chef_id);
                $chefDetail['flag'] = 1;
                $chefDetail['food_name'] = $req->dish_name;
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new ChefFoodItemNotification($chefDetail));
                }
                return response()->json(['message' => "Food Item Added Successfully", "success" => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
            }
            $query = FoodItem::with('category', 'heatingInstruction:id,title,description')->where($where);
            if ($req->day) {
                $query->whereRaw("JSON_CONTAINS(foodAvailibiltyOnWeekdays,'\"$req->day\"')");
            }

            $data = $query->get(['*']);
            $chefData = chef::where('id', $req->chef_id)->first(['chefAvailibilityWeek']);
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

                if (in_array($req->todaysWeekDay, $chefData->chefAvailibilityWeek)) {
                    // Determine availability based on the condition
                    $value['available'] = $req->todaysWeekDay ? in_array($req->todaysWeekDay, $value->foodAvailibiltyOnWeekdays) : false;
                } else {
                    $value['available'] = false;
                }
            }
            $chefData = chef::select('rating')->where('id', $req->chef_id)->first();
            return response()->json(["data" => $data, 'rating' => $chefData->rating, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
            return response()->json(['message' => 'updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function addNewAlternativeContact(Request $req)
    {
        if (!$req->chef_id || !$req->mobile) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            $newAlternativeContact = new ChefAlternativeContact();
            $newAlternativeContact->chef_id = $req->chef_id;
            $newAlternativeContact->mobile = str_replace("-", "", $req->mobile);
            $newAlternativeContact->save();
            return response()->json(['message' => 'Added successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function getAllAlternativeContacts(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            return response()->json(['data' => ChefAlternativeContact::where('chef_id', $req->chef_id)->get(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function updateStatusOfAlternativeContact(Request $req)
    {
        if (!$req->id) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            ChefAlternativeContact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
            if ($req->newPassword !== $req->confirmPassword) {
                return response()->json(['message' => 'new password does not matched', 'success' => false], 500);
            }
            $chefDetail = chef::find($req->chef_id);
            if ($chefDetail) {
                $chefDetail->makeVisible('password');
                if (Hash::check($req->currentPassword, $chefDetail['password'])) {
                    $chefDetail->makeHidden('password');
                    chef::where('id', $req->chef_id)->update(['password' => Hash::make($req->newPassword)]);
                    return response()->json(['message' => 'password updated successfully', 'success' => false], 200);
                } else {
                    return response()->json(['message' => 'current password is invalid', 'success' => false], 500);
                }
            } else {
                return response()->json(['message' => 'current password is invalid', 'success' => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function sendProfileForReview(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            $newProfileForReview = new ChefProfileReviewByAdmin();
            $newProfileForReview->chef_id = $req->chef_id;
            $newProfileForReview->save();
            chef::where('id', $req->chef_id)->update(['status' => 2]);
            RequestForUpdateDetails::where(['chef_id' => $req->chef_id, 'status' => 1])->update(['status' => 2]);
            return response()->json(['message' => 'Request Submitted successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function requestForUpdate(Request $req)
    {
        if (!$req->chef_id || !$req->request_for || !$req->message) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            $alreadyPending = RequestForUpdateDetails::orderBy('created_at', 'desc')->where('chef_id', $req->chef_id)->whereIn('status', [0, 1])->first();
            if ($alreadyPending) {
                return response()->json(['message' => 'you already have pending or approved request for update', 'success' => false], 500);
            }
            $newRequest = new RequestForUpdateDetails();
            $newRequest->chef_id = $req->chef_id;
            $newRequest->request_for = $req->request_for;
            $newRequest->message = $req->message;
            $newRequest->save();

            $request_query = RequestForUpdateDetails::orderBy('created_at', 'desc')->where('chef_id', $req->chef_id)->first();

            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new RequestQueryNotification($request_query));
            }
            return response()->json(['message' => 'Request Submitted successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again after sometime !', 'success' => false], 500);
        }
    }

    function getApprovedUpdaterequest(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all the required fields', 'success' => false], 400);
        }
        try {
            $data = RequestForUpdateDetails::orderBy('created_at', 'desc')->where(['chef_id' => $req->chef_id, 'status' => 1])->first();
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
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
            return response()->json(["message" => 'please fill all required fields', "success" => false], 400);
        }

        try {
            chef::where('id', $req->chef_id)->update([
                'chefAvailibilityWeek' => $req->chefAvailibilityWeek,
                'chefAvailibilityFromTime' => $req->chefAvailibilityFromTime,
                'chefAvailibilityToTime' => $req->chefAvailibilityToTime,
                'chefAvailibilityStatus' => $req->chefAvailibilityStatus
            ]);
            return response()->json(['message' => 'Availibilty updated successfully', 'success' => false], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function VerifyChefEmail(Request $req)
    {
        if (!$req->id) {
            return response()->json(["message" => 'please fill all the details', "success" => false], 400);
        }
        try {
            $checkVerification = chef::find($req->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                chef::where('id', $req->id)->update(['email_verified_at' => Carbon::now(), 'is_email_verified' => 1]);
                $chefDetails = chef::find($req->id);
                Mail::to(trim($chefDetails->email))->send(new HomeshefChefEmailVerifiedSuccessfully($chefDetails));
                return response()->json(['message' => 'Email has been verified successfully', 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function chefRegisterationRequest(Request $req)
    {
        try {
            $chefRequestExist = ShefRegisterationRequest::where("email", $req->email)->first();
            if ($chefRequestExist) {
                return response()->json(['message' => "This email has already rerquest for register please use another email!", "success" => false], 400);
            }
            $chefRequestExist = ShefRegisterationRequest::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefRequestExist) {
                return response()->json(['message' => "This mobileno has already rerquest for register please use another mobileno!", "success" => false], 400);
            }
            $chef = new ShefRegisterationRequest();
            $chef->first_name = ucfirst($req->first_name);
            $chef->last_name = ucfirst($req->last_name);
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
            return response()->json(['message' => 'Requested successfully!', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getChefRegisterationRequest(Request $req)
    {
        try {
            $totalRecords = ShefRegisterationRequest::count();
            $skip = $req->page * 10;
            $data = ShefRegisterationRequest::skip($skip)->take(10)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again after sometime !', 'success' => false], 500);
        }
    }

    function updateFoodItemAppprovedStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "approved_status" => 'required',
        ], [
            "id.required" => "please fill food id",
            "approved_status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            FoodItem::where('id', $req->id)->update(['approved_status' => $req->approved_status, 'approvedAt' => Carbon::now()->toDateTimeString()]);
            $foodItem = FoodItem::with('chef:first_name,last_name,email,id')->find($req->id);
            $chef = chef::find($foodItem->chef['id']);
            $chefDetail = [
                'food_id' => $foodItem['id'],
                'id' => $foodItem['chef']['id'],
                'email' => $foodItem['chef']->email,
                'full_name' => (ucfirst($foodItem['chef']->first_name) . ' ' . ucfirst($foodItem['chef']->last_name)),
                'food_name' => $foodItem['dish_name'],
            ];
            if ($foodItem['approved_status'] == 'approved') {
                Mail::to(trim($chefDetail['email']))->send(new HomeshefFoodItemStatusChange($chefDetail));
            }
            $chef->notify(new foodItemstatusChangeMail($chefDetail));
            return response()->json(["message" => 'Updated successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
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
            "chef_id.required" => "please fill chef id",
            "user_id.required" => "please fill user id",
            "review_id.required" => "please fill review id",
            "reason.required" => "please fill reason",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $request = new chefReviewDeleteRequest();
            $request->chef_id = $req->chef_id;
            $request->user_id = $req->user_id;
            $request->review_id = $req->review_id;
            $request->reason = $req->reason;
            $request->save();
            ChefReview::where('id', $req->review_id)->update(['requestedForDeletion' => 1]);
            $chefReviewDeleteRequest = chefReviewDeleteRequest::with('chef')->orderByDesc('created_at')->where(['chef_id' => $req->chef_id, 'user_id' => $req->user_id, 'review_id' => $req->review_id])->first();
            Log::info($chefReviewDeleteRequest);
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new requestForChefReviewDelete($chefReviewDeleteRequest));
            }
            return response()->json(["message" => 'Request has been raised successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
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
            "chef_id.required" => "please fill chef id",
            "user_id.required" => "please fill user id",
            "review_id.required" => "please fill review id",
            "reason.required" => "please fill reason",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $request = new RequestForUserBlacklistByChef();
            $request->chef_id = $req->chef_id;
            $request->user_id = $req->user_id;
            $request->reason = $req->reason;
            $request->save();

            $blacklistRequest = RequestForUserBlacklistByChef::with(['user', 'chef'])->orderByDesc('created_at')->where(['chef_id' => $req->chef_id, 'user_id' => $req->user_id])->first();
            ChefReview::where('id', $req->review_id)->update(['requestedForBlackList' => 1]);
            $request = new Request();
            $request->merge([
                "chef_id" => $req->chef_id,
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
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteMyFoodItem(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill chef id",
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
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function addChefSuggestions(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
        ], [
            "chef_id.required" => "please fill chef id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if (!File::exists("storage/chef/suggestions")) {
            File::makeDirectory("storage/chef/suggestions", $mode = 0777, true, true);
        }
        try {
            DB::beginTransaction();
            $storedPath = $req->file('sample_pic')->store('chef/suggestions', 'public');
            $filename = asset('/storage', $storedPath);

            $chefsuggestion = new ChefSuggestion();
            $chefsuggestion->related_to = $req->related_to;
            $chefsuggestion->message = $req->message;
            $chefsuggestion->sample_pic = $filename;
            $chefsuggestion->chef_id = $req->chef_id;

            $chefsuggestion->save();
            DB::commit();
            return response()->json(['message' => 'Suggestion Added successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateChefTaxInformation(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
        ], [
            "chef_id.required" => "Please mention chef_id",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            // Retrieve the chef's data
            $chef = Chef::find($req->chef_id);

            if (!$chef) {
                return response()->json(['message' => 'Chef not found', 'success' => false], 404);
            }

            // Delete existing GST image if it exists
            if ($req->hasFile('gst_image') && $chef->gst_image) {
                $existingGstImagePath = public_path(str_replace(asset('storage/'), '', $chef->gst_image));
                if (File::exists($existingGstImagePath)) {
                    // Attempt to delete the file
                    if (!unlink($existingGstImagePath)) {
                        return response()->json(['message' => 'Failed to delete existing GST image', 'success' => false], 500);
                    }
                }
            }

            // Delete existing QST image if it exists
            if ($req->hasFile('qst_image') && $chef->qst_image) {
                $existingQstImagePath = public_path(str_replace(asset('storage/'), '', $chef->qst_image));
                if (File::exists($existingQstImagePath)) {
                    // Attempt to delete the file
                    if (!unlink($existingQstImagePath)) {
                        return response()->json(['message' => 'Failed to delete existing QST image', 'success' => false], 500);
                    }
                }
            }

            // Store new GST image
            if ($req->hasFile('gst_image')) {
                $filename = $req->file('gst_image')->store('/chef/TaxInformation');
                $chef->gst_image = asset('storage/' . $filename);
            }

            // Store new QST image
            if ($req->hasFile('qst_image')) {
                $filenames = $req->file('qst_image')->store('/chef/TaxInformation');
                $chef->qst_image = asset('storage/' . $filenames);
            }

            // Update GST and QST numbers
            $chef->gst_no = $req->gst_no;
            $chef->qst_no = $req->qst_no;
            $chef->save();

            DB::commit();

            return response()->json(['message' => "Tax Information Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again!', 'success' => false], 500);
        }
    }

    public function getChefOrders(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $query = SubOrders::query();
            $query->where('chef_id', $req->chef_id)->with('Orders.user', 'OrderItems.foodItem', 'OrderTrack');

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

            if ($req->status != 'Accepted') {
                $query->where('status', $req->status);
            } else if ($req->status != 'Accepted') {
                $query->where('status', '!=', 'Pending')->where('status', '!=', "Rejected");
            }

            $data = $query->get();



            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    public function getChefSubOrder(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["message" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = SubOrders::where('chef_id', $req->chef_id)->where('sub_order_id', $req->sub_order_id)->with('orderItems.foodItem')->with('Orders')->first();
            $customer = User::where('id', $data->orders->user_id)->first();
            $trackDetails = OrderTrackDetails::where('track_id', $data->track_id)->first();
            Log::info($trackDetails);
            return response()->json(["data" => $data, "customer" => $customer, "trackDetails" => $trackDetails, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    public function updateChefOrderStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "track_id" => 'required',
            "status" => 'required',
            'sub_order_id' => 'required',
        ], [
            "track_id.required" => "please fill track_id",
            "status.required" => "please fill status",
            "sub_order_id.required" => "please fill sub order_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            SubOrders::where('sub_order_id', $req->sub_order_id)->update(['status' => $req->status]);
            OrderTrackDetails::create([
                'track_id' => $req->track_id,
                'status' => $req->status,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }
}
