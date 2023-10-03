<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Http\Controllers\utility\commonFunctions;
use App\Mail\HomeshefCustomerEmailVerifiedSuccessfully;
use App\Mail\HomeshefUserEmailVerificationMail;
use App\Models\Admin;
use App\Models\Adminsetting;
use App\Models\Cart;
use App\Models\Kitchentype;
use App\Models\NoRecordFound;
use App\Models\PaymentCredentialsCardData;
use App\Models\PaymentCredentialsPayPalData;
use App\Models\ShippingAddresse;
use App\Models\User;
use App\Models\chef;
use App\Models\ChefReview;
use App\Models\FoodItem;
use App\Models\FoodItemReview;
use App\Models\Pincode;
use App\Models\UserChefReview;
use App\Models\UserContact;
use App\Models\UserFoodReview;
use App\Notifications\Chef\NewChefReviewNotification;
use App\Notifications\Chef\NewReviewNotification;
use App\Notifications\Customer\CustomerContactUsNotification;
use App\Notifications\Customer\CustomerProfileUpdateNotification;
use App\Notifications\Customer\CustomerRegisterationNotification;
use App\Notifications\Customer\CustomerSearchNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Response;
use Stripe\Customer;

class UserController extends Controller
{
    function UserRegisteration(Request $req)
    {
        $userExist = User::where("email", $req->email)->first();
        if ($userExist) {
            return response()->json(['message' => "This email is already register please use another email!", "success" => false], 400);
        }
        $userExist = User::where('mobile', str_replace("-", "", $req->mobile))->first();
        if ($userExist) {
            return response()->json(['message' => "This mobileno is already register please use another mobileno!", "success" => false], 400);
        }
        try {

            DB::beginTransaction();
            $user = new User();
            $user->fullname = $req->fullname;
            $user->mobile = str_replace("-", "", $req->mobile);
            $user->email = $req->email;
            $user->password = Hash::make($req->password);
            $user->save();
            $userDetail = User::find($user->id);
            Mail::to(trim($req->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new CustomerRegisterationNotification($userDetail));
            }
            DB::commit();
            return response()->json(['message' => 'Register successfully!', "data" => $userDetail, 'success' => true], 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            ;
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function UserLogin(Request $req)
    {
        $rules = [
            "mobile" => 'required',
            "password" => 'required',
        ];
        $validate = Validator::make($req->all(), $rules);
        if ($validate->fails()) {
            return response()->json(["error" => ' please fill all the details', 'success' => false], 500);
        }

        $userDetails = User::where("mobile", str_replace("-", "", $req->mobile))->first();
        if ($userDetails) {
            $userDetails->makeVisible('password');
            if ($userDetails && Hash::check($req->password, $userDetails->password)) {
                $userDetails->makeHidden('password');
                return response()->json(['message' => 'Logged in successfully!', 'data' => $userDetails, 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
            }
        } else {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
    }

    function updateUserDetailStatus(Request $req)
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
            User::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getChefsByPostalCode(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'postal_code' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
        }

        try {
            $serviceExist = Pincode::where('pincode', str_replace(" ", "", strtoupper($req->postal_code)))->where('status', 1)->first();
            if ($serviceExist) {
                $lat_long_result_array = $this->get_lat_long($req->postal_code);

                if ($lat_long_result_array["result"] == 1) {
                    /***** Now from the customer postal code we need to find the ******/
                    $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
                    foreach ($selected_postal_code as &$value) {
                        $value = str_replace(" ", "", strtoupper($value));
                    }
                }
                if ($req->filter == 'true') {
                    $minPrice = $req->input('min');
                    if ($req->input('max') > 300) {
                        $maxPrice = 99999999999999999;
                    } else {
                        $maxPrice = $req->input('max');
                    }
                    $skip = $req->page * 12;
                    $query = chef::whereIn('postal_code', $selected_postal_code)->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1);
                    if ($req->rating) {
                        $query->where('rating', '<=', $req->rating);
                    }
                    $query->whereHas('foodItems', function ($query) use ($maxPrice, $minPrice, $req) {
                        $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
                        $query->where('price', '>=', $minPrice)->where('price', '<=', $maxPrice);
                        if ($req->foodType) {
                            $query->whereIn('foodTypeId', $req->foodType);
                        }
                        if ($req->spicyLevel) {
                            $query->whereIn('spicyLevel', $req->spicyLevel);
                        }
                        if ($req->allergies) {
                            $query->whereIn('allergies', $req->allergies);
                        }
                    });
                    $total = $query->count();
                    $data = $query->skip($skip)->limit(12)->get();
                    return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
                } else {
                    $query = chef::whereIn('postal_code', $selected_postal_code)->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1)->whereHas('foodItems', function ($query) use ($req) {
                        $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
                    });
                    if ($req->refresh) {
                        $skip = ($req->page + 1) * 12;
                        $data = $query->limit($skip)->get();
                    } else {
                        $skip = $req->page * 12;
                        $data = $query->skip($skip)->limit(12)->get();
                    }
                    $total = $query->count();
                    return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
                }
            } else {
                return response()->json(['message' => 'Service not availbale now', 'ServiceNotAvailable' => true, 'success' => false], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function get_lat_long($val)
    {
        try {
            $postalCode = str_replace(" ", "", strtoupper($val));
            $url = "https://maps.googleapis.com/maps/api/geocode/xml?address=" . $postalCode . ",canada&sensor=false&key=" . env('GOOGLE_MAP_KEY');
            $result = Http::get($url);
            $xml = simplexml_load_string($result->body());
            if ($xml->status == 'OK') {
                $latitude = (float) $xml->result->geometry->location->lat;
                $longitude = (float) $xml->result->geometry->location->lng;
                $data = [
                    'result' => 1,
                    'lat' => $latitude,
                    'long' => $longitude
                ];
                return $data;
            } else {
                $data = ['result' => 0];
                return $data;
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
    }

    public function findout_postal_with_radius($cust_pc_lat, $cust_pc_long)
    {

        $admin_setting = Adminsetting::first();
        $radius = $admin_setting->radius != "" ? $admin_setting->radius : 1;
        $postal_codes = Pincode::where('status', 1)->get();

        /*find distance between customer lat/long to the lat/long of the service
        postal code and store it into the array if its distance is equal or less then the
         radius
       */

        // define GMAPIK in contstant file.
        $gmApiK = env('GOOGLE_MAP_KEY');
        $origin = $cust_pc_lat . ',' . $cust_pc_long;
        $selected_postal_code = array();
        // dd($selected_postal_code);
        foreach ($postal_codes as $key => $postal_val) {
            $destination = $postal_val->latitude . ',' . $postal_val->longitude;
            // dd($destination , $origin);
            $routes = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&alternatives=true&sensor=false&departure_time=now&key=" . $gmApiK))->routes;

            if (count($routes) != 0) {
                $dist = $routes[0]->legs[0]->distance->text;
                $distance = explode(" ", $dist)[0];
                Log::info("", [$distance, $radius]);
                if ($distance <= $radius) {
                    // create a array of selected postal code
                    array_push($selected_postal_code, $postal_val->pincode);
                }
            } else {
                // still add if cant find routes using api
                array_push($selected_postal_code, $postal_val->pincode);
            }
        }

        //return the select postal code to the calling function
        return $selected_postal_code;
    }

    function getChefsByPostalCodeAndCuisineTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'postal_code' => "required",
            'kitchen_type_id' => "required",
            'todaysWeekDay' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {

            $lat_long_result_array = $this->get_lat_long($req->postal_code);

            if ($lat_long_result_array["result"] == 1) {
                /***** Now from the customer postal code we need to find the ******/
                $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
                foreach ($selected_postal_code as &$value) {
                    $value = str_replace(" ", "", strtoupper($value));
                }
            }

            $cuisine = Kitchentype::find($req->kitchen_type_id);
            $query = chef::whereIn('postal_code', $selected_postal_code)->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->whereJsonContains('kitchen_types', $cuisine->kitchentype)->where('chefAvailibilityStatus', 1)->whereHas('foodItems', function ($query) use ($req) {
                $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
            });
            if ($req->refresh) {
                $skip = ($req->page + 1) * 12;
                $data = $query->limit($skip)->get();
            } else {
                $skip = $req->page * 12;
                $data = $query->skip($skip)->limit(12)->get();
            }
            $total = $query->count();
            return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }

    }


    function calculateDistanceUsingTwoLatlong(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'postal_code_1' => "required",
            'postal_code_2' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {
            $admin_setting = Adminsetting::first();
            $multiChefOrderRadius = $admin_setting->multiChefOrderAllow != "" ? $admin_setting->multiChefOrderAllow : 5;
            $data = ['success' => true];
            //define GMAPIK in contstant file.
            $gmApiK = env('GOOGLE_MAP_KEY');

            $latlongOfPostalCode1 = $this->get_lat_long($req->postal_code_1);
            $origin = $latlongOfPostalCode1["lat"] . ',' . $latlongOfPostalCode1["long"];

            $latlongOfPostalCode2 = $this->get_lat_long($req->postal_code_2);
            $destination = $latlongOfPostalCode2["lat"] . ',' . $latlongOfPostalCode2["long"];

            $routes = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&alternatives=true&sensor=false&departure_time=now&key=" . $gmApiK))->routes;

            if (count($routes) != 0) {
                $dist = $routes[0]->legs[0]->distance->text;
                $distance = explode(" ", $dist)[0];
                if ($distance <= $multiChefOrderRadius) {
                    $data['message'] = 'Distance is within the limit and can make multi chef order';
                    $data['status'] = 1;
                } else {
                    $data['message'] = 'Distance is not within the limit multi chef order is not allowed';
                    $data['status'] = 2;
                }
                $data['distance'] = $distance . ' KM';

            } else {
                $data['message'] = 'Unable to find Routes so allow to add multi chef order';
                $data['status'] = 1;
            }

            return response()->json($data);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all th required fields', "success" => false], 400);
        }
        try {
            $data = chef::with(['chefDocuments', 'alternativeContacts'])->find($req->chef_id);
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function googleSigin(Request $req)
    {
        try {

            $userExist = User::where('email', $req->email)->first();
            if ($userExist) {
                return response()->json(['message' => 'Login successfully!', "data" => $userExist, 'success' => true], 200);
            } else {
                DB::beginTransaction();
                $user = new User();
                $user->fullname = $req->name;
                $user->email = $req->email;
                $user->social_id = $req->id;
                $user->social_type = $req->provider;
                $user->email_verified_at = Carbon::now();
                $user->save();
                $userDetail = User::find($user->id);
                Mail::to(trim($req->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new CustomerRegisterationNotification($userDetail));
                }
                DB::commit();
                return response()->json(['message' => 'Register successfully!', "data" => $userDetail, 'success' => true], 201);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function recordNotFoundSubmit(Request $req)
    {
        if (!$req->user_id) {
            $validator = Validator::make($req->all(), [
                'postal_code' => "required",
                'fullname' => "required",
                'email' => "required",
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
            }
        }
        try {
            $admins = Admin::all();
            $newData = new NoRecordFound();
            if ($req->user_id) {
                $userDetail = User::find($req->user_id);
                $ifSameData = NoRecordFound::orderBy('created_at', 'desc')->where(['postal_code' => strtolower($req->postal_code), 'email' => $userDetail->email])->first();
                if (!$ifSameData) {
                    $newData->postal_code = strtolower($req->postal_code);
                    $newData->full_name = $userDetail->fullname;
                    $newData->email = $userDetail->email;
                    $newData->save();
                    $search = NoRecordFound::orderBy('created_at', 'desc')->where(['email' => $userDetail->email, 'postal_code' => strtolower($req->postal_code)])->first();
                    foreach ($admins as $admin) {
                        $admin->notify(new CustomerSearchNotification($search));
                    }
                }
            } else {
                $ifSameData = NoRecordFound::orderBy('created_at', 'desc')->where(['postal_code' => strtolower($req->postal_code), 'email' => $req->email])->first();
                if (!$ifSameData) {
                    $newData->postal_code = strtolower($req->postal_code);
                    $newData->full_name = $req->fullname;
                    $newData->email = $req->email;
                    $newData->save();
                    $search = NoRecordFound::orderBy('created_at', 'desc')->where(['email' => $req->email, 'postal_code' => strtolower($req->postal_code)])->first();
                    foreach ($admins as $admin) {
                        $admin->notify(new CustomerSearchNotification($search));
                    }
                }
            }
            return response()->json(['message' => 'added successfull', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function addUpdateShippingAddress(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => "required",
            'first_name' => "required",
            'last_name' => "required",
            'mobile_no' => "required",
            'postal_code' => "required",
            'city' => "required",
            'state' => "required",
            'full_address' => "required",
            'address_type' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }

        try {
            if ($req->id) {
                $updateData = $req->all();
                $recordToUpdate = ShippingAddresse::find($req->id);
                if ($recordToUpdate) {
                    $recordToUpdate->update($updateData);
                    return response()->json(['message' => 'Address updated successfully', 'success' => true], 200);
                } else {
                    return response()->json(['message' => 'Address not found', 'success' => false], 404);
                }
            } else {
                $newAdrees = new ShippingAddresse();
                $newAdrees->user_id = $req->user_id;
                $newAdrees->first_name = $req->first_name;
                $newAdrees->last_name = $req->last_name;
                $newAdrees->mobile_no = str_replace("-", '', $req->mobile_no);
                $newAdrees->postal_code = $req->postal_code;
                $newAdrees->city = $req->city;
                $newAdrees->state = $req->state;
                // $newAdrees->landmark = $req->landmark;
                // $newAdrees->locality = $req->locality;
                $newAdrees->full_address = $req->full_address;
                $newAdrees->address_type = $req->address_type;

                if ($req->default_address) {
                    $newAdrees->default_address = 1;
                    ShippingAddresse::where(['user_id' => $req->user_id, 'default_address' => 1])->update(['default_address' => 0]);
                } else {
                    $count = ShippingAddresse::where(['user_id' => $req->user_id, 'default_address' => 1])->count();
                    if ($count < 1) {
                        $newAdrees->default_address = 1;
                    }
                }
                $newAdrees->save();
                return response()->json(['message' => 'Added successfully', 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function getAllShippingAdressOfUser(Request $req)
    {
        if (!$req->user_id) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            return response()->json(['data' => ShippingAddresse::where(['user_id' => $req->user_id])->get(), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function changeDefaultShippingAddress(Request $req)
    {
        if (!$req->id || !$req->user_id) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            ShippingAddresse::where(['user_id' => $req->user_id, 'default_address' => 1])->update(['default_address' => 0]);
            ShippingAddresse::where('id', $req->id)->update(['default_address' => 1]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function deleteShippingAddress(Request $req)
    {
        if (!$req->id) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            ShippingAddresse::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
    }

    function getUserDetails(Request $req)
    {
        if (!$req->user_id) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            return response()->json(['data' => User::find($req->user_id), 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
    }

    function updateUserDetail(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => "required",
            'fullname' => "required",
            'mobile' => "required",
            'email' => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            $user = User::where('id', $req->user_id)->first();
            $update = [
                'fullname' => $req->fullname,
                'email' => $req->email,
                'mobile' => str_replace('-', '', $req->mobile)
            ];
            if ($req->mobile && !$user->mobile_verified_at) {
                $update['mobile_verified_at'] = Carbon::now();
            }
            if ($req->email != $user->email) {
                $update['email_verified_at'] = Carbon::now();
            }
            User::where('id', $req->user_id)->update($update);
            $customer = User::find($req->user_id);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new CustomerProfileUpdateNotification($customer));
            }
            return response()->json(['message' => 'User updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
    }

    function addUserContacts(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                "are_you_a" => 'required',
                "full_name" => 'required',
                "email" => 'required',
                "subject" => 'required',
                "message" => "required",
            ],
            [
                "are_you_a.required" => "please fill Are you a?",
                "full_name.required" => "please fill full_name",
                "email.required" => "please select email",
                "subject.required" => "please select subject",
                "message.required" => "please fill message",
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $contact = new UserContact();
            $contact->are_you_a = $req->are_you_a;
            $contact->full_name = $req->full_name;
            $contact->email = $req->email;
            $contact->subject = $req->subject;
            $contact->message = $req->message;
            $contact->save();

            $contactUSDetails = UserContact::orderBy('created_at', 'desc')->where('email', $req->email)->first();

            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new CustomerContactUsNotification($contactUSDetails));
            }
            return response()->json(['message' => "Submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateContactStatus(Request $req)
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
            UserContact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getUserContact(Request $req)
    {
        try {
            $totalRecords = UserContact::count();
            $skip = $req->page * 10;
            $items = UserContact::orderBy('created_at', 'desc')->skip($skip)->take(10)->get();
            return response()->json(['data' => $items, 'TotalRecords' => $totalRecords], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function ChefReview(Request $req)
    {

        $validator = Validator::make(
            $req->all(),
            [
                "user_id" => 'required',
                "chef_id" => 'required',
                "star_rating" => "required|integer|min:1|max:5",
                "message" => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" => 'please fill all the details', "success" => false], 400);
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
            $chef = chef::find($req->chef_id);
            $chef->notify(new NewChefReviewNotification($reviewDetails));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new NewReviewNotification($reviewDetails));
            }
            $this->updateChefrating($req->chef_id);
            return response()->json(['message' => "Submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateChefrating($chef_id)
    {
        $allReview = ChefReview::select('star_rating')->where(['chef_id' => $chef_id, 'status' => 1])->get();
        $totalNoReview = ChefReview::where(['chef_id' => $chef_id, 'status' => 1])->count();
        $totalStars = 0;
        foreach ($allReview as $value) {
            $totalStars = $totalStars + $value['star_rating'];
        }
        $rating = $totalStars / $totalNoReview;
        chef::where('id', $chef_id)->update(['rating' => $rating]);
    }

    function deleteChefReview(Request $req)
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
            ChefReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    function getChefReview(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'required',
        ], [
            "chef_id.required" => "please fill chef_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            // $totalRecords = ChefReview::where(['chef_id' => $req->chef_id, 'status' => 1])->count();
            // $skip = $req->page * 10;
            // $data = ChefReview::where(['chef_id' => $req->chef_id, 'status' => 1])->skip($skip)->take(10)->with('user:fullname,id')->get();
            // return response()->json([
            //     'data' => $data,
            //     'TotalRecords' => $totalRecords,
            //     'success' => true
            // ], 200);

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            $chefId = $req->chef_id;

            // Get the reviews related to the chef where status is 1
            $reviews = ChefReview::where(['chef_id' => $chefId, 'status' => 1])->with('user:fullname,id')->get();

            // Get user IDs for the reviews
            $userIds = $reviews->pluck('user_id')->unique();

            // Count the number of reviews with status 2 for each user
            $userReviewCounts = [];
            foreach ($userIds as $userId) {
                $userReviewCounts[$userId] = ChefReview::where(['chef_id' => $chefId, 'user_id' => $userId, 'status' => 2])->count();
            }

            // Add the IsBlacklistAllowed property to the reviews
            $reviews->each(function ($review) use ($userReviewCounts) {
                $userId = $review->user_id;
                $review->IsBlacklistAllowed = ($userReviewCounts[$userId] >= 2);
            });

            $totalRecords = $reviews->count(); // Count the total number of reviews

            return response()->json([
                'data' => $reviews,
                'TotalRecords' => $totalRecords,
                'success' => true
            ], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getCountOftheChefAvailableForNext30Days(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "postal_code" => 'required',
        ], [
            "postal_code.required" => "please fill postal_code",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $dateList = [];

            for ($i = 1; $i <= 30; $i++) {
                $date = now()->addDays($i);
                $dayName = $date->shortDayName;

                if ($dayName == 'Sun') {
                    $weekdayShort = 'Su';
                } elseif ($dayName == 'Thu') {
                    $weekdayShort = 'Th';
                } else {
                    $weekdayShort = substr($dayName, 0, 1);
                }

                $dateList[] = [
                    // Full weekday name
                    'dayName' => $date->englishDayOfWeek,
                    // Full month name
                    'monthName' => $date->englishMonth,
                    // Day number
                    'dayNumber' => $date->day,

                    'weekday' => $dayName,
                    'weekdayShort' => $weekdayShort,
                    'formatted_date' => $date->format('M d'),
                    'iso_date' => $date->toDateString(),
                    'custom_date_format' => $date->format('d/m/Y')
                ];
            }

            // getting counts of the available shefs for next 14 days
            foreach ($dateList as &$val) {
                $query = chef::where('postal_code', strtoupper(str_replace(" ", "", $req->postal_code)));
                $query->where('chefAvailibilityStatus', 1)->whereJsonContains('chefAvailibilityWeek', $val['weekdayShort'])->whereHas('foodItems', function ($query) use ($val) {
                    $query->whereJsonContains('foodAvailibiltyOnWeekdays', $val['weekdayShort']);
                });
                $val['total'] = $query->count();
            }
            return response()->json(['data' => $dateList, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function VerifyUserEmail(Request $req)
    {
        if (!$req->id) {
            return response()->json(["message" => 'please fill all the details', "success" => false], 400);
        }
        try {
            $checkVerification = User::find($req->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                User::where('id', $req->id)->update(['email_verified_at' => Carbon::now()]);
                $userDetails = User::find($req->id);
                Mail::to(trim($userDetails->email))->send(new HomeshefCustomerEmailVerifiedSuccessfully($userDetails));
                return response()->json(['message' => 'Email has been verified successfully', 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function addOrUpdateFoodReview(Request $req)
    {
        try {
            $validator = Validator::make(
                $req->all(),
                [
                    "user_id" => 'required',
                    "food_id" => 'required',
                    "rating" => "required|integer|min:1|max:5",
                    "review" => 'required',
                ]
            );
            if ($validator->fails()) {
                return response()->json(["message" => 'please fill all the details', "success" => false], 400);
            }

            $imagePaths = [];
            if ($req->hasFile('images')) {
                $images = $req->file('images');
                foreach ($images as $image) {
                    $imagePath = $image->store('food_reviews/' . $req->chef_id . '/', "public");
                    array_push($imagePaths, asset('storage/' . $imagePath));
                }
            }
            $reviewExist = FoodItemReview::where(['user_id' => $req->user_id, 'food_id' => $req->food_id])->first();
            if ($reviewExist) {

                foreach ($reviewExist as $image) {
                    if (file_exists(str_replace(env('filePath'), '', $image))) {
                        unlink(str_replace(env('filePath'), '', $image));
                    }
                }

                FoodItemReview::where(['user_id' => $req->user_id, 'food_id' => $req->food_id])->update(
                    [
                        'user_id' => $req->user_id,
                        'food_id' => $req->food_id,
                        'rating' => $req->rating,
                        'review' => $req->review,
                        'reviewImages' => $imagePaths,
                    ]
                );
            } else {
                $review = new FoodItemReview();
                $review->user_id = $req->user_id;
                $review->food_id = $req->food_id;
                $review->rating = $req->rating;
                $review->review = $req->review;
                $review->reviewImages = $imagePaths;
                $review->save();
            }


            $allReview = FoodItemReview::select('rating')->where('food_id', $req->food_id)->get();
            $totalNoReview = FoodItemReview::where('food_id', $req->food_id)->count();
            $totalStars = 0;
            foreach ($allReview as $value) {
                $totalStars = $totalStars + $value['rating'];
            }
            $rating = $totalStars / $totalNoReview;
            FoodItem::where('id', $req->food_id)->update(['rating' => $rating]);

            if ($reviewExist) {
                return response()->json(['message' => 'Review updated successfully', 'success' => true], 200);
            } else {
                return response()->json(['message' => 'Added successfully', 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function getAllFoodReview(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "food_id" => 'required',
        ], [
            "food_id.required" => "please fill chef_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $totalRecords = FoodItemReview::where('chef_id', $req->chef_id)->count();
            $skip = $req->page * 10;
            $data = FoodItemReview::where('food_id', $req->food_id)->skip($skip)->take(10)->with('user:fullname,id')->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
                'success' => true
            ], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function updateUserFoodReviewStatus(Request $req)
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
            UserFoodReview::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deleteUserFoodReview(Request $req)
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
            $data = UserFoodReview::where('id', $req->id)->first();
            $images = json_decode($data->foodimage);
            foreach ($images as $image) {
                str_replace(env('filePath'), '', $image);
                if (file_exists(str_replace(env('filePath'), '', $image))) {
                    unlink(str_replace(env('filePath'), '', $image));
                }
            }
            UserFoodReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    function getAllUserFoodReviewsbyStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "status" => 'required',
        ], [
            "status.required" => "please fill status",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $skip = $req->input('page', 0) * 10;
            $query = UserFoodReview::orderBy('created_at', 'desc')->with(['chef', 'user', 'food']);
            if ($req->input('status') === '0') {
                $query->where('status', 0);
            } elseif ($req->input('status') === '1') {
                $query->where('status', 1);
            }
            $foodReviews = $query->skip($skip)->take(10)->get();
            $totalRecords = $foodReviews->count();
            return response()->json(['data' => $foodReviews, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }

    function updateUserChefReviewStatus(Request $req)
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
            UserChefReview::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deleteUserChefReview(Request $req)
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
            UserChefReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    function getAllUserChefReviewsbyStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "status" => 'required',
        ], [
            "status.required" => "please fill status",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $skip = $req->input('page', 0) * 10;
            $query = UserChefReview::orderBy('created_at', 'desc')->with(['chef', 'user']);
            if ($req->input('status') === '0') {
                $query->where('status', 0);
            } elseif ($req->input('status') === '1') {
                $query->where('status', 1);
            }
            $chefReviews = $query->skip($skip)->take(10)->get();
            $totalRecords = $chefReviews->count();
            return response()->json(['data' => $chefReviews, 'TotalRecords' => $totalRecords, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to again !', 'success' => false], 500);
        }
    }


}