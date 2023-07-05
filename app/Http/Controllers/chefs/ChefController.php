<?php

namespace App\Http\Controllers\chefs;

use App\Http\Controllers\Controller;
use App\Http\Controllers\users\UserController;
use App\Http\Controllers\utility\commonFunctions;
use App\Mail\HomeshefChefEmailVerification;
use App\Models\chef;
use App\Models\ChefDocument;
use App\Models\City;
use App\Models\DocumentItemField;
use App\Models\FoodItem;
use App\Models\ScheduleCall;
use App\Models\State;
use App\Models\User;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Models\Pincode;
use Illuminate\Support\Facades\Validator;
use Image; //Intervention Image
use File;

class ChefController extends Controller
{
    function ChefRegisteration(Request $req)
    {
        try {
            DB::beginTransaction();
            // $checkPinCode = Pincode::where('pincode', str_replace(" ", "", strtolower($req->pincode)))->first();
            // if (!$checkPinCode) {
            //     return response()->json(["msg" => 'we are not offering our services in this region', 'success' => false], 400);
            // }

            $chefExist = chef::where("email", $req->email)->first();
            if ($chefExist) {
                return response()->json(['error' => "This email is already register please use another email!", "success" => false], 400);
            }
            $chefExist = chef::where('mobile', str_replace("-", "", $req->mobile))->first();
            if ($chefExist) {
                return response()->json(['error' => "This mobileno is already register please use another mobileno!", "success" => false], 400);
            }
            $chef = new chef();
            $chef->first_name = ucfirst($req->first_name);
            $chef->last_name = ucfirst($req->last_name);
            $chef->date_of_birth = $req->date_of_birth;
            $chef->postal_code = str_replace(" ", "", ($req->postal_code));
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
                    "fullname" => ucfirst($req->first_name) . " " . ucfirst($req->last_name),
                    "mobile" => $req->mobile,
                    "email" => $req->email,
                    "password" => $req->password
                ]);
                $UserController->UserRegisteration($request);
            }

            Mail::to(trim($req->email))->send(new HomeshefChefEmailVerification($chefDetail));

            DB::commit();
            return response()->json(['message' => 'Register successfully!', "data" => $chefDetail, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
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
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }

        try {
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
            }

            $result = chef::where('id', $req->chef_id)->update([
                "first_name" => ucfirst($req->first_name),
                "last_name" => ucfirst($req->last_name),
                "type" => ucfirst($req->type),
                "sub_type" => ucfirst($req->sub_type),
                "address_line1" => htmlspecialchars(ucfirst($req->address_line1)),
                "postal_code" => strtoupper($req->postal_code),
                "profile_pic" => isset($profile) ? $profile : '',
                "latitude" => isset($req->latitude) ? $req->latitude : '',
                "longitude" => isset($req->longitude) ? $req->longitude : '',
                "city" => isset($req->city) ? $req->city : '',
                "state" => isset($req->state) ? $req->state : ''
            ]);
            return response()->json(["msg" => "profile updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
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
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        $chefDetail = chef::where("email", $req->email)->first();

        if ($chefDetail) {
            $chefDetail->makeVisible('password');
            if (Hash::check($req->password, $chefDetail['password'])) {
                $chefDetail->makeHidden('password');
                return response()->json(['message' => 'Logged in successfully!', 'data' => $chefDetail, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = chef::whereId($req->chef_id)->with([
                'chefDocuments' => fn($q) => $q->select('id', 'chef_id', 'document_field_id', 'field_value')
                    ->with([
                        'documentItemFields' => fn($qr) => $qr->select('id', 'document_item_list_id', 'field_name', 'type', 'mandatory')
                    ])
            ])
                ->first();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateChefPrimaryEmail(Request $req)
    {
        if (!$req->chef_id || !$req->new_email) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $chefDetails = chef::find($req->chef_id);
            if ($chefDetails->email == $req->new_email) {
                return response()->json(['error' => 'Trying to use an existing primary email. Please use another email.', "success" => false], 500);
            }

            $isNewEmailAlreadyRegistered = chef::where('email', $req->new_email)->first();
            if (!$isNewEmailAlreadyRegistered) {
                $chefDetails->email = trim($req->new_email);
                $chefDetails->is_email_verified = 0;
                $chefDetails->email_verified_at = null;
                $chefDetails->save();
                Mail::to(trim($req->new_email))->send(new HomeshefChefEmailVerification($chefDetails));
                return response()->json(['msg' => "Updated sucessfully", "success" => true], 200);
            } else {
                return response()->json(["error" => "This email is already registerd with Homeshef", "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateSocialMediaLinks(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => "please fill all the required fields", "success" => false], 400);
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
            $chef->save();
            return response()->json(['msg' => 'Updated successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
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
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $chef = chef::find($req->chef_id);
            $chef->bank_name = $req->bank_name;
            $chef->account_number = $req->account_number;
            $chef->transit_number = $req->transit_number;
            $chef->institution_number = $req->institution_number;
            $chef->save();
            return response()->json(['msg' => "updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateDocuments(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['error' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $path = 'chef/' . $req->chef_id;

            $chef = chef::find($req->chef_id);

            // store address proof 
            if (isset($req->address_proof) && $req->hasFile('address_proof_path')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->address_proof_path))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->address_proof_path));
                }
                $storedPath = $req->file('address_proof_path')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["address_proof_path" => asset('storage/' . $storedPath), "address_proof" => $req->address_proof]);
            }

            // store ID proof 1
            if ($req->hasFile('id_proof_path1')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->id_proof_path1))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->id_proof_path1));
                }
                $storedPath = $req->file('id_proof_path1')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["id_proof_path1" => asset('storage/' . $storedPath)]);
            }

            // store ID proof 2
            if ($req->hasFile('id_proof_path2')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->id_proof_path1))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->id_proof_path1));
                }
                $storedPath = $req->file('id_proof_path2')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["id_proof_path2" => asset('storage/' . $storedPath)]);
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
                    }
                }
            }

            // Additional fields which has values in files
            if (isset($req->files) && isset($req->id)) {
                $fieldsArray = $req->input('id');
                $filesArray = $req->file('files');
                Log::info($fieldsArray);
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
                    }
                }
            }

            DB::commit();
            return response()->json(['msg' => "updated successfully", 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateKitchen(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['error' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $path = 'chef/' . $req->chef_id;
            $chef = chef::find($req->chef_id);

            if ($req->hasFile('chef_banner_image')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->chef_banner_image))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->chef_banner_image));
                }
                $storedPath = $req->file('chef_banner_image')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["chef_banner_image" => asset('storage/' . $storedPath)]);
            }

            if ($req->hasFile('chef_card_image')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->chef_card_image))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->chef_card_image));
                }
                $storedPath = $req->file('chef_card_image')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["chef_card_image" => asset('storage/' . $storedPath)]);
            }

            $update = [
                "kitchen_types" => $req->kitchen_types,
                "about_kitchen" => $req->about_kitchen,
                "kitchen_name" => $req->kitchen_name,
            ];
            chef::where('id', $req->chef_id)->update($update);
            DB::commit();
            return response()->json(["msg" => "updated successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function updateSpecialBenifits(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['error' => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $path = 'chef/' . $req->chef_id;
            $chef = chef::find($req->chef_id);
            if ($req->hasFile('are_you_a_file_path')) {
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $chef->are_you_a_file_path))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $chef->are_you_a_file_path));
                }
                $storedPath = $req->file('are_you_a_file_path')->store($path, 'public');
                chef::where("id", $req->chef_id)->update(["are_you_a_file_path" => asset('storage/' . $storedPath), "are_you_a" => $req->are_you_a]);
                return response()->json(["msg" => "updated successfully", "success" => true], 200);
            } else {
                return response()->json(["msg" => "please upload proof of " . $req->are_you_a, "success" => false], 500);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function AddContactData(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => "please fill all the required fields ", "success" => false], 400);
        }
        try {
            $contact = new contact();
            $contact->chef_id = $req->chef_id;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();
            return response()->json(['msg' => 'Submitted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function chefScheduleAnCall(Request $req)
    {
        if (!$req->chef_id || !$req->date || !$req->slot) {
            return response()->json(["msg" => 'Please fill all the details', 'success' => false]);
        }
        try {
            $slotNotAvailable = ScheduleCall::where(['date' => $req->date, 'slot' => $req->slot])->first();
            if ($slotNotAvailable) {
                return response()->json(['msg' => 'Slot not available select another slot', 'success' => false], 500);
            }
            $SameChefSameSlot = ScheduleCall::where(['chef_id' => $req->chef_id, 'slot' => $req->slot])->first();
            if ($SameChefSameSlot) {
                return response()->json(['msg' => 'Already booked on same slot', 'success' => false]);
            }
            DB::beginTransaction();
            $scheduleNewCall = new ScheduleCall();
            $scheduleNewCall->chef_id = $req->chef_id;
            $scheduleNewCall->date = $req->date;
            $scheduleNewCall->slot = $req->slot;
            $scheduleNewCall->save();
            DB::commit();
            return response()->json(["msg" => 'Call has been scheduled successfully', 'success' => true]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function chefAddNewOrUpdateFoodItem(Request $req)
    {
        try {
            if ($req->food_id && $req->chef_id) {

            } else {
                $validator = Validator::make(
                    $req->all(),
                    [
                        'dish_name' => 'required',
                        'description' => 'required',
                        'dishImage' => 'required|image',
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
                        'comments' => 'required',

                    ],
                    [
                        'dish_name.required' => 'Please mention dish name',
                        'description.required' => 'Please add dish discription',
                        'dishImage.required' => 'please add dish image',
                        'dishImage.image' => 'please select image file only',
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
                        'comments.required' => 'Please mention some comments for the food'
                    ]
                );

                if ($validator->fails()) {
                    return response()->json(["error" => $validator->errors(), "success" => false], 400);
                }

                $OGfilePath = "";
                $filename_thumb = "";
                if ($req->hasFile('dishImage')) {
                    $directoryPath = 'storage/foodItem/';
                    $directoryPathThumbnail = 'storage/foodItem/thumbnail/';
                    if (!file_exists($directoryPath)) {
                        mkdir($directoryPath, 0755, true);
                    }
                    if (!file_exists($directoryPathThumbnail)) {
                        mkdir($directoryPathThumbnail, 0755, true);
                    }
                    $image = Image::make($req->file('dishImage'));
                    $name_gen = hexdec(uniqid()) . '.' . $req->file('dishImage')->getClientOriginalExtension();
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
                $foodItem->foodAvailibiltyOnWeekdays = $req->foodAvailibiltyOnWeekdays;
                $foodItem->orderLimit = $req->orderLimit;
                $foodItem->foodTypeId = $req->foodTypeId;
                $foodItem->spicyLevel = $req->spicyLevel;
                $foodItem->geographicalCuisine = $req->geographicalCuisine;
                $foodItem->otherCuisine = $req->otherCuisine;
                $foodItem->ingredients = $req->ingredients;
                $foodItem->otherIngredients = $req->otherIngredients;
                $foodItem->nutritions = $req->nutritions;
                $foodItem->dietary = $req->dietary;
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
                return response()->json(['msg' => "Food Item Added Successfully", "success" => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }

    }

    function getMyFoodItems(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(["msg" => 'Please fill all the details', 'success' => false]);
        }
        try {
            $data = FoodItem::where('chef_id', $req->chef_id)->get();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

    function getFoodItem(Request $req)
    {
        if (!$req->food_id) {
            return response()->json(["msg" => 'Please fill all the details', 'success' => false]);
        }
        try {
            $data = FoodItem::where('id', $req->food_id)->first();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again after sometime !', 'success' => false], 500);
        }
    }

}