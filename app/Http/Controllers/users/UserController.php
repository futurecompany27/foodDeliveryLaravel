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
use App\Models\Chef;
use App\Models\ChefReview;
use App\Models\FoodItem;
use App\Models\FoodItemReview;
use App\Models\Order;
use App\Models\OrderTrackDetails;
use App\Models\Pincode;
use App\Models\SubOrders;
use App\Models\UserChefReview;
use App\Models\UserContact;
use App\Models\UserFoodReview;
use App\Notifications\Chef\NewChefReviewNotification;
use App\Notifications\Chef\ChefFoodReviewNotification;
use App\Notifications\Chef\NewReviewNotification;
use App\Notifications\Customer\CustomerContactUsNotification;
use App\Notifications\Customer\CustomerProfileUpdateNotification;
use App\Notifications\Customer\CustomerRegisterationNotification;
use App\Notifications\Customer\CustomerSearchNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\File;
use Tymon\JWTAuth\Facades\JWTAuth;
use Response;
use Stripe\Customer;
use Illuminate\Support\Facades\Cache;
use Tymon\JWTAuth\Exceptions\JWTException;

class UserController extends Controller
{

    function UserRegisteration(Request $req)
    {
        $userExist = User::where("email", $req->email)->first();
        if ($userExist) {
            return response()->json(['message' => "This email is already register Please use another email!", "success" => false], 400);
        }
        $userExist = User::where('mobile', str_replace("-", "", $req->mobile))->first();
        if ($userExist) {
            return response()->json(['message' => "This mobile no is already register Please use another mobile no!", "success" => false], 400);
        }
        try {

            DB::beginTransaction();
            $user = new User();
            $user->firstName = $req->firstName;
            $user->lastName = $req->lastName;
            $user->mobile = str_replace("-", "", $req->mobile);
            $user->email = $req->email;
            $user->password = Hash::make($req->password);
            $user->save();
            $userDetail = User::find($user->id);

            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($req->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new CustomerRegisterationNotification($userDetail));
            }
            DB::commit();
            return response()->json(['message' => 'You are all set to start ordering your food now ! Thank you for registering with us', "data" => $userDetail, 'success' => true], 201);
        } catch (\Exception $e) {
            Log::info($e);
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function UserLogin(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "mobile" => 'required',
            "password" => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'error' => $validator->errors()->first(), 'data' => ""], 400);
        }
        // Normalize the mobile number
        $mobile = str_replace("-", "", $req->mobile);
        // Retrieve user details
        $userDetails = User::where("mobile", $mobile)->first();
        // Check if user exists
        if (!$userDetails) {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
        // Check user status
        if ($userDetails->status == 0) {
            return response()->json(['message' => 'You have an inactive account. Please get in touch with the Homeplete team.'], 403);
        }
        // Make password visible for checking
        $userDetails->makeVisible('password');
        // Validate password
        if (!Hash::check($req->password, $userDetails->password)) {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
        if (!$token = auth('user')->attempt(['mobile' => $mobile, 'password' => $req->password])) {
            return response()->json(['message' => 'Invalid credentials!', 'success' => false], 400);
        }
        // Hide password again
        $userDetails->makeHidden('password');
        return response()->json(['message' => 'Login Successfully!', 'user_id' => auth()->user()->id, 'token' => User::createToken($token), 'data' => [
            'user_id' => $userDetails->id,
            'firstName' => $userDetails->firstName, // Add First Name
            'lastName' => $userDetails->lastName,   // Add Last Name
            'token' => $token
        ], 'success' => true], 200);
        // return User::createToken($token);
    }

    public function userProfile()
    {
        // Retrieve the authenticated user
        $user = auth()->guard('user')->user();
        // Check if the user is authenticated
        if (!$user) {
            return response()->json(['message' => 'User not found', 'success' => false], 404);
        }
        // Return the user's profile
        return response()->json(['success' => true, 'data' => $user], 200);
    }

    public function userLogout()
    {
        auth()->guard('user')->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    public function userRefreshToken()
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
                'expires_in' => JWTAuth::factory()->getTTL() * 60, // Convert to seconds
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


    function updateUserDetailStatus(Request $req)
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
            User::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    // function getChefsByPostalCode(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         'postal_code' => "required",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
    //     }

    //     try {
    //         $postalCode = substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3);
    //         $serviceExist = Pincode::where('pincode', $postalCode)->where('status', 1)->first();

    //         if (!$serviceExist) {
    //             return response()->json(['message' => 'Service not available now', 'ServiceNotAvailable' => true, 'success' => false], 200);
    //         }

    //         $lat_long_result_array = $this->get_lat_long($req->postal_code);
    //         if ($lat_long_result_array["result"] != 1) {
    //             return response()->json(['message' => 'Invalid postal code', 'success' => false], 400);
    //         }

    //         $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
    //         $selected_postal_code = array_map(function ($value) {
    //             return str_replace(" ", "", strtoupper($value));
    //         }, $selected_postal_code);

    //         $query = Chef::where(function ($q) use ($selected_postal_code) {
    //             foreach ($selected_postal_code as $postalCode) {
    //                 $q->orWhere('postal_code', 'like', $postalCode . '%');
    //             }
    //         })->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1);


    //         if ($req->filter == 'true') {
    //             $minPrice = $req->input('min');
    //             $maxPrice = $req->input('max') > 300 ? 99999999999999999 : $req->input('max');

    //             if ($req->has('rating')) {
    //                 $query->where('rating', '<=', intval($req->rating));
    //             }

    //             $query->whereHas('foodItems', function ($q) use ($minPrice, $maxPrice, $req) {
    //                 $q->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay)
    //                     ->where('price', '>=', $minPrice)
    //                     ->where('price', '<=', $maxPrice);

    //                 // Additional filters
    //                 if ($req->has('foodType')) {
    //                     $q->whereIn('foodTypeId', $req->foodType);
    //                 }
    //                 if ($req->has('spicyLevel')) {
    //                     $q->whereIn('spicyLevel', $req->spicyLevel);
    //                 }
    //                 if ($req->has('allergies')) {
    //                     $q->whereIn('allergies', $req->allergies);
    //                 }
    //             });
    //         }
    //         if ($req->has('kitchen_name')) {
    //             $query->where(function ($q) use ($req) {
    //                 $q->where('kitchen_name', 'like', '%' . $req->kitchen_name . '%')
    //                     ->orWhereHas('foodItems', function ($q) use ($req) {
    //                         $q->where('dish_name', 'like', '%' . $req->kitchen_name . '%');
    //                     });
    //             });
    //         }

    //         $skip = $req->page * 12;
    //         $data = $query->skip($skip)->take(12)->get();
    //         $total = $query->count();

    //         return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }



    // function getChefsByPostalCode(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         'postal_code' => "required",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
    //     }

    //     try {
    //         $serviceExist = Pincode::where('pincode', substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3))->where('status', 1)->first();
    //         if ($serviceExist) {
    //             $lat_long_result_array = $this->get_lat_long($req->postal_code);

    //             if ($lat_long_result_array["result"] == 1) {
    //                 /***** Now from the customer postal code we need to find the ******/
    //                 $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
    //                 foreach ($selected_postal_code as &$value) {
    //                     $value = str_replace(" ", "", strtoupper($value));
    //                 }
    //             }
    //             if ($req->filter == 'true') {
    //                 $minPrice = $req->input('min');
    //                 if ($req->input('max') > 300) {
    //                     $maxPrice = 99999999999999999;
    //                 } else {
    //                     $maxPrice = $req->input('max');
    //                 }
    //                 $skip = $req->page * 12;
    //                 $query = Chef::whereIn('postal_code', $selected_postal_code)->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1);

    //                 // $rating = $req->rating ? intval($req->rating) : null;
    //                 if ($req->rating) {
    //                     $query->where('rating', '<=', intval($req->rating));
    //                 }

    //                 if ($req->query) {
    //                     $search = mb_strtolower(trim(request()->input('query')));
    //                     $query->whereRaw('LOWER(`kitchen_name`) LIKE ?', ['%' . strtolower($search) . '%']);
    //                 }
    //                 $query->whereHas('foodItems', function ($query) use ($maxPrice, $minPrice, $req) {
    //                     $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
    //                     $query->where('price', '>=', $minPrice)->where('price', '<=', $maxPrice);
    //                     if ($req->foodType) {
    //                         $query->whereIn('foodTypeId', $req->foodType);
    //                     }
    //                     if ($req->spicyLevel) {
    //                         $query->whereIn('spicyLevel', $req->spicyLevel);
    //                     }
    //                     if ($req->allergies) {
    //                         $query->whereIn('allergies', $req->allergies);
    //                     }
    //                     if ($req->query) {
    //                         $search = mb_strtolower(trim(request()->input('query')));
    //                         $query->whereRaw('LOWER(`dish_name`) LIKE ?', ['%' . strtolower($search) . '%']);
    //                     }
    //                 });

    //                 $total = $query->count();
    //                 $data = $query->get();
    //                 // $data = $query->skip($skip)->limit(12)->get();
    //                 return response()->json(['success' => true, 'data' => $data, 'total' => $total], 200);
    //             } else {
    //                 Log::info('', $selected_postal_code);
    //                 $query = Chef::where(function ($q) use ($selected_postal_code) {
    //                     foreach ($selected_postal_code as $postalCode) {
    //                         $q->orWhere('postal_code', 'like', $postalCode . '%');
    //                     }
    //                 })->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1)->whereHas('foodItems', function ($query) use ($req) {
    //                     $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
    //                 });
    //                 if ($req->refresh) {
    //                     $skip = ($req->page + 1) * 12;
    //                     $data = $query->limit($skip)->get();
    //                 } else {
    //                     $skip = $req->page * 12;
    //                     $data = $query->skip($skip)->limit(12)->get();
    //                 }
    //                 $total = $query->count();
    //                 return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
    //             }
    //         } else {
    //             return response()->json(['message' => 'Service not availbale now', 'ServiceNotAvailable' => true, 'success' => false], 200);
    //         }
    //     } catch (Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }



    function getChefsByPostalCode(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'postal_code' => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
        }

        // Generate a unique cache key based on the postal code and filter parameters
        $cacheKey = 'chefs_' . $req->postal_code . '_' . md5(json_encode($req->all()));
        Log::info('cacheKey', [$cacheKey]);
        // Try to get the data from cache
        if (Cache::has($cacheKey)) {
            $cachedData = Cache::get($cacheKey);
            Log::info('cachedData', [$cachedData]);
            return response()->json($cachedData, 200);
        }

        try {
            $serviceExist = Pincode::where('pincode', substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3))
                ->where('status', 1)
                ->first();

            if ($serviceExist) {
                $lat_long_result_array = $this->get_lat_long($req->postal_code);
                $selected_postal_code[] = '';
                if ($lat_long_result_array["result"] == 1) {
                    $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
                    foreach ($selected_postal_code as &$value) {
                        $value = str_replace(" ", "", strtoupper($value));
                    }
                }

                if ($req->filter == 'true') {
                    $minPrice = $req->input('min');
                    $maxPrice = $req->input('max') > 300 ? 99999999999999999 : $req->input('max');
                    $skip = $req->page * 12;

                    $query = Chef::whereIn('postal_code', $selected_postal_code)
                        ->where('status', 1)
                        ->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)
                        ->where('chefAvailibilityStatus', 1);

                    if ($req->rating) {
                        $query->where('rating', '<=', intval($req->rating));
                    }

                    $query->whereHas('foodItems', function ($query) use ($maxPrice, $minPrice, $req) {
                        $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay)
                            ->where('price', '>=', $minPrice)
                            ->where('price', '<=', $maxPrice);

                        if ($req->foodType) {
                            $query->whereIn('foodTypeId', $req->foodType);
                        }

                        if ($req->spicyLevel) {
                            $query->whereIn('spicyLevel', $req->spicyLevel);
                        }

                        if ($req->allergies) {
                            $query->whereIn('allergies', $req->allergies);
                        }

                        if ($req->query) {
                            $search = mb_strtolower(trim($req->input('query')));
                            $query->whereRaw('LOWER(`dish_name`) LIKE ?', ['%' . $search . '%']);
                        }
                    });

                    $total = $query->count();
                    $data = $query->get();

                    // Store the result in cache
                    Cache::put($cacheKey, ['data' => $data, 'total' => $total, 'success' => true], now()->addMinutes(1440));

                    return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
                } else {
                    Log::info("///////ELSE PART///////////", [$selected_postal_code]);

                    $query = Chef::where(function ($q) use ($selected_postal_code) {
                        foreach ($selected_postal_code as $postalCode) {
                            $q->orWhere('postal_code', 'like', $postalCode . '%');
                        }
                    })
                        ->where('status', 1)
                        ->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)
                        ->where('chefAvailibilityStatus', 1)
                        ->whereHas('foodItems', function ($query) use ($req) {
                            $query->whereJsonContains('foodAvailibiltyOnWeekdays', $req->todaysWeekDay);
                        });

                    $searchQuery = trim(strtolower($req->kitchen_name));

                    if ($searchQuery !== '') {
                        $foodItems = FoodItem::where('dish_name', 'LIKE', '%' . $searchQuery . '%')
                            ->with('chef')
                            ->get();

                        if ($foodItems->isNotEmpty()) {
                            $formattedData = [];
                            foreach ($foodItems as $foodItem) {
                                if ($foodItem->chef) {
                                    $formattedData[] = [
                                        'dish_id' => $foodItem->id,
                                        'dish_name' => $foodItem->dish_name,
                                        'chef_id' => $foodItem->chef->id,
                                        'firstName' => $foodItem->chef->firstName,
                                        'lastName' => $foodItem->chef->lastName,
                                    ];
                                }
                            }

                            if (!empty($formattedData)) {
                                Cache::put($cacheKey, ['data' => $formattedData, 'success' => true], now()->addMinutes(1440)); // store cache in 24 hr

                                return response()->json(['data' => $formattedData, 'success' => true], 200);
                            }
                        }

                        $chefs = Chef::where('kitchen_name', 'LIKE', '%' . $searchQuery . '%')
                            ->where('status', 1)
                            ->with('foodItems')
                            ->get();

                        if ($chefs->isNotEmpty()) {
                            $formattedChefs = [];
                            foreach ($chefs as $chef) {
                                $formattedChefs[] = [
                                    'chef_id' => $chef->id,
                                    'chef_name' => $chef->firstName,
                                    'kitchen_name' => $chef->kitchen_name,
                                    'food_items' => $chef->foodItems,
                                ];
                            }

                            Cache::put($cacheKey, ['data' => $formattedChefs, 'success' => true], now()->addMinutes(1440)); //24 hr expiry cache

                            return response()->json(['data' => $formattedChefs, 'success' => true], 200);
                        }
                    }

                    if ($req->refresh) {
                        $skip = ($req->page + 1) * 12;
                        $data = $query->limit($skip)->get();
                    } else {
                        $skip = $req->page * 12;
                        $data = $query->skip($skip)->limit(12)->get();
                    }
                    $total = $query->count();

                    Cache::put($cacheKey, ['total' => $total, 'success' => true, 'data' => $data], now()->addMinutes(1)); //24 hr expiry cache

                    return response()->json(['total' => $total, 'success' => true, 'data' => $data], 200);
                }
            } else {
                return response()->json(['message' => 'Service not available now', 'ServiceNotAvailable' => true, 'success' => false], 200);
            }
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
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
        } catch (Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
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
            $selected_postal_code[] = '';
            if ($lat_long_result_array["result"] == 1) {
                /***** Now from the customer postal code we need to find the ******/
                $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
                foreach ($selected_postal_code as &$value) {
                    $value = str_replace(" ", "", strtoupper($value));
                }
            }

            $cuisine = Kitchentype::find($req->kitchen_type_id);
            $query = Chef::where(function ($q) use ($selected_postal_code) {
                foreach ($selected_postal_code as $postalCode) {
                    $q->orWhere('postal_code', 'like', $postalCode . '%');
                }
            })->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->whereJsonContains('kitchen_types', $cuisine->kitchentype)->where('chefAvailibilityStatus', 1)->whereHas('foodItems', function ($query) use ($req) {
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
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // function calculateDistanceUsingTwoLatlong(Request $req)
    // {
    //     Log::info('calculateDistanceUsing', [$req->all()]);
    //     $validator = Validator::make($req->all(), [
    //         'postal_code_1' => "required",
    //         'postal_code_2' => "required",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
    //     }
    //     try {
    //         $admin_setting = Adminsetting::first();
    //         $multiChefOrderRadius = $admin_setting->multiChefOrderAllow != "" ? $admin_setting->multiChefOrderAllow : 5;
    //         $data = ['success' => true];

    //         //define GMAPIK in contstant file.
    //         $gmApiK = env('GOOGLE_MAP_KEY');

    //         $latlongOfPostalCode1 = $this->get_lat_long($req->postal_code_1);

    //         $origin = $latlongOfPostalCode1["lat"] . ',' . $latlongOfPostalCode1["long"];

    //         $latlongOfPostalCode2 = $this->get_lat_long($req->postal_code_2);
    //         $destination = $latlongOfPostalCode2["lat"] . ',' . $latlongOfPostalCode2["long"];
    //         Log::info('Distance LOg: ', [$destination]);
    //         $routes = json_decode(file_get_contents("https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&alternatives=true&sensor=false&departure_time=now&key=" . $gmApiK))->routes;

    //         if (count($routes) != 0) {
    //             $dist = $routes[0]->legs[0]->distance->text;
    //             $distance = explode(" ", $dist)[0];
    //             if ($distance <= $multiChefOrderRadius) {
    //                 $data['message'] = 'Distance is within the limit and can make multi chef order';
    //                 $data['status'] = 1;
    //             } else {
    //                 $data['message'] = 'Distance is not within the limit multi chef order is not allowed';
    //                 $data['status'] = 2;
    //             }
    //             $data['distance'] = $distance . ' KM';
    //         } else {
    //             $data['message'] = 'Unable to find Routes so allow to add multi chef order';
    //             $data['status'] = 1;
    //         }

    //         return response()->json($data);
    //     } catch (Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }


    function calculateDistanceUsingTwoLatlong(Request $req)
    {
        Log::info('calculateDistanceUsing', [$req->all()]);

        // Validate input data
        $validator = Validator::make($req->all(), [
            'postal_code_1' => "required",
            'postal_code_2' => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }

        try {
            // Get admin setting
            $admin_setting = Adminsetting::first();
            $multiChefOrderRadius = $admin_setting->multiChefOrderAllow != "" ? $admin_setting->multiChefOrderAllow : 5;

            // Define GMAPIK in constant file or from .env
            $gmApiK = env('GOOGLE_MAP_KEY');

            // Get latitude and longitude of postal code 1
            $latlongOfPostalCode1 = $this->get_lat_long($req->postal_code_1);
            if (!$latlongOfPostalCode1 || !isset($latlongOfPostalCode1['lat']) || !isset($latlongOfPostalCode1['long'])) {
                return response()->json(['message' => 'Invalid postal code 1 coordinates.', 'success' => false], 400);
            }
            $origin = $latlongOfPostalCode1['lat'] . ',' . $latlongOfPostalCode1['long'];

            // Get latitude and longitude of postal code 2
            $latlongOfPostalCode2 = $this->get_lat_long($req->postal_code_2);
            if (!$latlongOfPostalCode2 || !isset($latlongOfPostalCode2['lat']) || !isset($latlongOfPostalCode2['long'])) {
                return response()->json(['message' => 'Invalid postal code 2 coordinates.', 'success' => false], 400);
            }
            $destination = $latlongOfPostalCode2['lat'] . ',' . $latlongOfPostalCode2['long'];

            // Log the destination coordinates for debugging
            Log::info('Distance Log: ', [$destination]);

            // Call Google Maps API to get the routes
            $url = "https://maps.googleapis.com/maps/api/directions/json?origin=" . urlencode($origin) . "&destination=" . urlencode($destination) . "&alternatives=true&sensor=false&departure_time=now&key=" . $gmApiK;

            // Fetch the response and check for errors
            $response = file_get_contents($url);
            if ($response === FALSE) {
                throw new Exception("Failed to retrieve data from Google Maps API");
            }

            // Decode the JSON response
            $routes = json_decode($response)->routes;

            // Check if routes are returned
            if (count($routes) != 0) {
                $dist = $routes[0]->legs[0]->distance->text;
                $distance = explode(" ", $dist)[0]; // Extract the numeric distance

                // Check if the distance is within the allowed radius
                if ($distance <= $multiChefOrderRadius) {
                    $data['message'] = 'Distance is within the limit and can make multi-chef order';
                    $data['status'] = 1;
                } else {
                    $data['message'] = 'Distance is not within the limit, multi-chef order is not allowed';
                    $data['status'] = 2;
                }
                $data['distance'] = $distance . ' KM';
            } else {
                // If no routes are found, allow the multi-chef order
                $data['message'] = 'Unable to find routes, allowing multi-chef order';
                $data['status'] = 1;
            }

            return response()->json($data);
        } catch (Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }



    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'Please fill all th required fields', "success" => false], 400);
        }
        try {
            $data = Chef::with(['chefDocuments', 'alternativeContacts'])->find($req->chef_id);
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function googleSigin(Request $req)
    {
        try {
            $userExist = User::where('email', $req->email)->first();

            if ($userExist) {
                // Generate JWT token
                $token = JWTAuth::fromUser($userExist);

                return response()->json([
                    'message' => 'You are logged in successfully',
                    'data' => $userExist,
                    'token' => $token, // Include JWT token in response
                    'success' => true
                ], 200);
            } else {
                DB::beginTransaction();

                $user = new User();
                $user->firstName = $req->firstName;
                $user->lastName = $req->lastName;
                $user->email = $req->email;
                $user->social_id = $req->id;
                $user->social_type = $req->provider;
                $user->email_verified_at = Carbon::now();
                $user->save();

                // Fetch user details
                $userDetail = User::find($user->id);

                // Send verification email (if enabled)
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($req->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
                    }
                } catch (Exception $e) {
                    Log::error($e);
                }

                // Notify all admins
                $admins = Admin::all();
                foreach ($admins as $admin) {
                    $admin->notify(new CustomerRegisterationNotification($userDetail));
                }

                DB::commit();

                // Generate JWT token for new user
                $token = JWTAuth::fromUser($user);

                return response()->json([
                    'message' => 'You are all set to start ordering your food now! Thank you for registering with us.',
                    'data' => $userDetail,
                    'token' => $token, // Include JWT token
                    'success' => true
                ], 201);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function recordNotFoundSubmit(Request $req)
    {
        $user = auth()->guard('user')->user();
        if (!$user) {
            $validator = Validator::make($req->all(), [
                'postal_code' => "required",
                'firstName' => "required",
                'lastName' => "required",
                'email' => "required",
            ]);
            if ($validator->fails()) {
                return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
            }
        }
        try {
            $admins = Admin::all();
            $newData = new NoRecordFound();
            // if ($req->user_id)
            if ($user) {
                $userDetail = User::find($user->id);
                $ifSameData = NoRecordFound::orderBy('created_at', 'desc')->where(['postal_code' => strtolower($req->postal_code), 'email' => $userDetail->email])->first();
                if (!$ifSameData) {
                    $newData->postal_code = strtolower($req->postal_code);
                    $newData->firstName = $userDetail->firstName;
                    $newData->lastName = $userDetail->lastName;
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
                    $newData->firstName = $req->firstName;
                    $newData->lastName = $req->lastName;
                    $newData->email = $req->email;
                    $newData->save();
                    $search = NoRecordFound::orderBy('created_at', 'desc')->where(['email' => $req->email, 'postal_code' => strtolower($req->postal_code)])->first();
                    foreach ($admins as $admin) {
                        $admin->notify(new CustomerSearchNotification($search));
                    }
                }
            }
            return response()->json(['message' => 'Your information has been saved', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getRecordNotFound(Request $req)
    {
        try {
            $searchHistory = NoRecordFound::orderBy('created_at', 'desc')->get();
            return response()->json(['message' => 'Search records fetched successfully', 'success' => true, 'data' => $searchHistory], 200);
        } catch (Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function addUpdateShippingAddress(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'user_id' => "required",
            'firstName' => "required",
            'lastName' => "required",
            'mobile_no' => "required",
            'postal_code' => "required",
            'state' => "required",
            'full_address' => "required",
            'address_type' => "required",
            'latitude' => "required",
            'longitude' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            $serviceExist = Pincode::where('pincode', substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3))->where('status', 1)->first();
            if ($serviceExist) {
                if ($req->id) {
                    $updateData = $req->all();
                    $recordToUpdate = ShippingAddresse::find($req->id);
                    if ($recordToUpdate) {
                        $recordToUpdate->update($updateData);
                        return response()->json(['message' => 'Delivery address updated successfully', 'success' => true], 200);
                    } else {
                        return response()->json(['message' => 'Address not found', 'success' => false], 404);
                    }
                } else {
                    $user = auth()->guard('user')->user();
                    $newAdrees = new ShippingAddresse();
                    // $newAdrees->user_id = $req->user_id;
                    $newAdrees->user_id = $user->id;
                    $newAdrees->firstName = $req->firstName;
                    $newAdrees->lastName = $req->lastName;
                    $newAdrees->mobile_no = str_replace("-", '', $req->mobile_no);
                    $newAdrees->postal_code = $req->postal_code;
                    $newAdrees->city = $req->city ? $req->city : '';
                    $newAdrees->state = $req->state;
                    // $newAdrees->landmark = $req->landmark;
                    // $newAdrees->locality = $req->locality;
                    $newAdrees->latitude = $req->latitude;
                    $newAdrees->longitude = $req->longitude;
                    $newAdrees->full_address = $req->full_address;
                    $newAdrees->address_type = $req->address_type;

                    if ($req->default_address) {
                        $newAdrees->default_address = 1;
                        ShippingAddresse::where(['user_id' => $user->id, 'default_address' => 1])->update(['default_address' => 0]);
                    } else {
                        $count = ShippingAddresse::where(['user_id' => $user->id, 'default_address' => 1])->count();
                        if ($count < 1) {
                            $newAdrees->default_address = 1;
                        }
                    }
                    $newAdrees->save();
                    return response()->json(['message' => 'Delivery address added successfuly', 'success' => true], 200);
                }
            } else {
                return response()->json(['message' => 'We are currently not offering our services in this region yet.', 'ServiceNotAvailable' => true, 'success' => false], 200);
            }
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getAllShippingAdressOfUser(Request $req)
    {
        try {
            $user = auth()->guard('user')->user();
            // Log::info('User Address' [$user]);
            $data = ShippingAddresse::where('user_id', $user->id)->get();
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
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
            return response()->json(['message' => 'Delivery address has been changed successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function deleteShippingAddress(Request $req)
    {
        if (!$req->id) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            ShippingAddresse::where('id', $req->id)->delete();
            // ShippingAddresse::where('user_id', $user->id)->delete();
            return response()->json(['message' => 'Your address has been deleted ', 'success' => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function getUserDetails(Request $req)
    {
        try {
            $user = auth()->guard('user')->user();

            return response()->json(['data' => $user, 'message' => 'User fetched successfully', 'success' => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function updateUserDetail(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'user_id' => "required",
            'firstName' => "required",
            'lastName' => "required",
            'mobile' => "required",
            'email' => "required",
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Please fill all the required field', 'success' => false], 400);
        }
        try {
            // $user = User::where('id', $req->user_id)->first();
            $user = auth()->guard('user')->user();
            $update = [
                'firstName' => $req->firstName,
                'lastName' => $req->lastName,
                'email' => $req->email,
                'mobile' => str_replace('-', '', $req->mobile)
            ];
            if ($req->mobile && !$user->mobile_verified_at) {
                $update['mobile_verified_at'] = Carbon::now();
            }
            if ($req->email != $user->email) {
                $update['email_verified_at'] = Carbon::now();
            }
            User::where('id', $user->id)->update($update);
            $customer = User::find($user->id);
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new CustomerProfileUpdateNotification($customer));
            }
            $data = User::where('id', $user->id)->first();
            return response()->json(['message' => 'Your profile has been updated.', 'data' => $data, 'success' => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function updatePaymentMethod(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'defaultPayment' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), 'success' => false], 400);
        }
        try {
            $auth = auth()->user();
            if ($req->defaultPayment) {
                $user = User::find($auth->id);
                $user->defaultPayment = $req->defaultPayment;
                $user->save();
            }
        } catch (Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    function addUserContacts(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                "are_you_a" => 'required',
                "firstName" => 'required',
                "lastName" => 'required',
                "email" => 'required',
                "subject" => 'required',
                "message" => "required",
            ],
            [
                "are_you_a.required" => "Please fill Are you a?",
                "firstName.required" => "Please fill first name",
                "lastName.required" => "Please fill last name",
                "email.required" => "Please select email",
                "subject.required" => "Please select subject",
                "message.required" => "Please fill message",
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $contact = new UserContact();
            $contact->are_you_a = $req->are_you_a;
            $contact->firstName = $req->firstName;
            $contact->lastName = $req->lastName;
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
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateContactStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required|exists:user_contacts,id',
            "status" => 'required',
        ], [
            "id.required" => "Please fill status",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            UserContact::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getUserContact(Request $req)
    {
        try {
            $totalRecords = UserContact::count();
            $skip = $req->page * 10;
            $items = UserContact::orderBy('created_at', 'desc')->skip($skip)->take(10)->get();
            return response()->json(['data' => $items, 'TotalRecords' => $totalRecords], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function ChefReview(Request $req)
    {
        $validator = Validator::make(
            $req->all(),
            [
                // "user_id" => 'required|exists:users,id',
                "chef_id" => 'required|exists:chefs,id',
                "star_rating" => "required",
                "message" => 'required',
            ]
        );
        if ($validator->fails()) {
            return response()->json(["message" =>  $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            $reviewExist = ChefReview::where(['user_id' => $user->id, 'chef_id' => $req->chef_id, 'status' => 1])->first();
            if ($reviewExist) {
                ChefReview::where(['user_id' => $user->id, 'chef_id' => $req->chef_id])->update(['star_rating' => $req->star_rating, 'message' => $req->message]);
            } else {
                $newReview = new ChefReview();
                $newReview->user_id = $user->id;
                $newReview->chef_id = $req->chef_id;
                $newReview->star_rating = $req->star_rating;
                $newReview->message = $req->message;
                $newReview->save();
            }
            $reviewDetails = ChefReview::orderBy('created_at', 'desc')->with(['user', 'chef'])->where(['user_id' => $user->id, 'chef_id' => $req->chef_id])->first();
            $reviewDetails['date'] = Carbon::now();
            $chef = Chef::find($req->chef_id);
            $chef->notify(new NewChefReviewNotification($reviewDetails));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new NewReviewNotification($reviewDetails));
            }
            $this->updateChefrating($req->chef_id);
            return response()->json(['message' => "Submitted successfully", "success" => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => $th->getMessage() . 'Oops! Something went wrong.', 'success' => false], 500);
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
        Chef::where('id', $chef_id)->update(['rating' => $rating]);
    }

    function deleteChefReview(Request $req)
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
            ChefReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    function getChefReview(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "chef_id" => 'sometimes|required|exists:chefs,id',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $query = ChefReview::where('status', 1);

            // Apply chef_id filter if provided
            if ($req->has('chef_id')) {
                $query->where('chef_id', $req->chef_id);
            }

            // Pagination logic
            $totalRecords = $query->count();
            $skip = $req->page * 10;
            $data = $query->skip($skip)->take(10)->with('user:firstName,lastName,id')->get();

            // $totalRecords = ChefReview::where(['chef_id' => $req->chef_id, 'status' => 1])->count();
            // $skip = $req->page * 10;
            // $data = ChefReview::where(['chef_id' => $req->chef_id, 'status' => 1])->skip($skip)->take(10)->with('user:firstName,lastName,id')->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
                'success' => true
            ], 200);

            ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

            // $chefId = $req->chef_id;
            // $chefId = ChefReview::where('chef_id',$req->chef_id)->first();
            // dd($chefId);
            // if(!$chefId){
            //     return response()->json([
            //         'message' => 'Review not found',
            //         'success' => false,
            //         'data' => '',
            //     ], 200);
            // }

            // // Get the reviews related to the chef where status is 1
            // $reviews = ChefReview::where(['chef_id' => $chefId, 'status' => 1])->with('user:firstName,lastName,id')->get();

            // // Get user IDs for the reviews
            // $userIds = $reviews->pluck('user_id')->unique();

            // // Count the number of reviews with status 2 for each user
            // $userReviewCounts = [];
            // foreach ($userIds as $userId) {
            //     $userReviewCounts[$userId] = ChefReview::where(['chef_id' => $chefId, 'user_id' => $userId, 'status' => 2])->count();
            // }

            // // Add the IsBlacklistAllowed property to the reviews
            // $reviews->each(function ($review) use ($userReviewCounts) {
            //     $userId = $review->user_id;
            //     $review->IsBlacklistAllowed = ($userReviewCounts[$userId] >= 2);
            // });

            // $totalRecords = $reviews->count(); // Count the total number of reviews

            // return response()->json([
            //     'data' => $reviews,
            //     'TotalRecords' => $totalRecords,
            //     'success' => true
            // ], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getCountOftheChefAvailableForNext30Days(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "postal_code" => 'required',
        ], [
            "postal_code.required" => "Please fill postal_code",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $dateList = [];

            for ($i = 0; $i <= 30; $i++) {
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

            $lat_long_result_array = $this->get_lat_long($req->postal_code);
            $selected_postal_code[] = '';
            if ($lat_long_result_array["result"] == 1) {
                /***** Now from the customer postal code we need to find the ******/
                $selected_postal_code = $this->findout_postal_with_radius($lat_long_result_array["lat"], $lat_long_result_array["long"]);
                foreach ($selected_postal_code as &$value) {
                    $value = str_replace(" ", "", strtoupper($value));
                }
            }

            // getting counts of the available shefs for next 14 days
            foreach ($dateList as &$val) {
                $query = Chef::where(function ($q) use ($selected_postal_code) {
                    foreach ($selected_postal_code as $postalCode) {
                        $q->orWhere('postal_code', 'like', $postalCode . '%');
                    }
                });
                if ($req->kitchen_type_id) {
                    $cuisine = Kitchentype::find($req->kitchen_type_id);
                    $query->whereJsonContains('kitchen_types', $cuisine->kitchentype);
                }
                $query->where('chefAvailibilityStatus', 1)->where('status', 1)->whereJsonContains('chefAvailibilityWeek', $val['weekdayShort'])->whereHas('foodItems', function ($query) use ($val) {
                    $query->whereJsonContains('foodAvailibiltyOnWeekdays', $val['weekdayShort']);
                });
                $val['total'] = $query->count();
            }
            return response()->json(['data' => $dateList, 'success' => true], 200);
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function VerifyUserEmail(Request $req)
    {
        // if (!$req->id) {
        //     return response()->json(["message" => 'Please fill all the details', "success" => false], 400);
        // }
        try {
            $user = auth()->guard('user')->user();
            $checkVerification = User::find($user->id);
            if ($checkVerification->email_verified_at) {
                return response()->json(['message' => 'Email has been already verified successfully', 'status' => 1, 'success' => true], 200);
            } else {
                User::where('id', $user->id)->update(['email_verified_at' => Carbon::now()]);
                $userDetails = User::find($user->id);
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($userDetails->email))->send(new HomeshefCustomerEmailVerifiedSuccessfully($userDetails));
                    }
                } catch (Exception $e) {
                    Log::error($e);
                }
                return response()->json(['message' => 'Email has been verified successfully', 'success' => true], 200);
            }
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function addOrUpdateFoodReview(Request $req)
    {
        try {
            $validator = Validator::make(
                $req->all(),
                [
                    "user_id" => 'required|exists:users,id',
                    "food_id" => 'required|exists:food_items,id',
                    "star_rating" => "required|integer|min:1|max:5",
                    "message" => 'required',
                    "image" => 'nullable|image|mimes:jpeg,jpg,png|max:250',
                ]
            );
            if ($validator->fails()) {
                return response()->json(["message" => 'Please fill all the details', "success" => false], 400);
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
                        'rating' => $req->star_rating,
                        'review' => $req->message,
                        'reviewImages' => $imagePaths,
                    ]
                );
            } else {
                $review = new FoodItemReview();
                $review->user_id = $req->user_id;
                $review->food_id = $req->food_id;
                $review->rating = $req->star_rating;
                $review->review = $req->message;
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
            $reviewDetails = FoodItemReview::where(['user_id' => $req->user_id, 'food_id' => $req->food_id])->with('user:id,firstName,lastName')->first();
            // Log::info($reviewDetails);
            // if ($reviewExist) {
            //     $chefDetail = FoodItem::find($req->food_id);
            //     Log::info($chefDetail);
            //     $chefDetail->chef_id->notify(new NewChefReviewNotification($reviewDetails));

            //     return response()->json(['message' => 'Review updated successfully', 'success' => true], 200);
            // }
            if ($reviewExist) {
                // Assuming you have the relationship setup correctly
                $chefDetail = FoodItem::with('chef')->find($req->food_id);
                $message = ' updated a food review on ';

                if ($chefDetail && $chefDetail->chef) {
                    $chefDetail->chef->notify(new ChefFoodReviewNotification($reviewDetails, $message));
                } else {
                    return response()->json(['message' => 'Chef details not found for food item ID:' . ($req->food_id), 'success' => false], 400);
                }

                return response()->json(['message' => 'Review updated successfully', 'success' => true], 200);
            } else {
                $chefDetail = FoodItem::with('chef')->find($req->food_id);
                $message = ' sent a food review to you on ';
                if ($chefDetail && $chefDetail->chef) {
                    $chefDetail->chef->notify(new ChefFoodReviewNotification($reviewDetails, $message));
                } else {
                    return response()->json(['message' => 'Chef details not found for food item ID:' . ($req->food_id), 'success' => false], 400);
                }
                return response()->json(['message' => 'Added successfully', 'success' => true], 200);
            }
        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // function getAllFoodReview(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "food_id" => 'required',
    //     ], [
    //         "food_id.required" => "Please fill chef_id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $totalRecords = FoodItemReview::where('chef_id', $req->chef_id)->count();
    //         $skip = $req->page * 10;
    //         $data = FoodItemReview::where('food_id', $req->food_id)->skip($skip)->take(10)->with('user:firstName,lastName,id')->get();
    //         return response()->json([
    //             'data' => $data,
    //             'TotalRecords' => $totalRecords,
    //             'success' => true
    //         ], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    function getAllFoodReview(Request $req)
    {
        // Validate request parameters
        $validator = Validator::make($req->all(), [
            "food_id" => 'required|integer',
        ], [
            "food_id.required" => "Please provide the food ID.",
            "food_id.integer" => "Food ID must be a valid integer.",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            $foodId = $req->food_id;

            // Get total reviews for the food
            $totalRecords = FoodItemReview::where('food_id', $foodId)->count();

            // Pagination logic (assuming page starts from 1)
            $page = (int) $req->page ?: 1;
            $skip = ($page - 1) * 10;

            // Fetch reviews with user details (limited fields)
            $data = FoodItemReview::where('food_id', $foodId)
                ->skip($skip)
                ->take(10)
                ->with(['user' => function ($query) {
                    $query->select('id', 'firstName', 'lastName');
                }])
                ->with(['food' => function ($query) {
                    $query->select('id', 'dish_name', 'dishImageThumbnail');
                }]) // Load food details
                ->get();

            return response()->json([
                "data" => $data,
                "TotalRecords" => $totalRecords,
                "success" => true,
            ], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json([
                "error" => "An unexpected error occurred. Please try again later.",
                "success" => false,
            ], 500);
        }
    }

    function updateUserFoodReviewStatus(Request $req)
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
            UserFoodReview::where('id', $req->id)->update(['status' => $req->status]);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function deleteUserFoodReview(Request $req)
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
        } catch (\Exception $th) {
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
            "status.required" => "Please fill status",
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
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateUserChefReviewStatus(Request $req)
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
            UserChefReview::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function deleteUserChefReview(Request $req)
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
            UserChefReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
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
            "status.required" => "Please fill status",
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
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getUserOrders(Request $req)
    {
        try {
            $user = auth()->guard('user')->user();
            $query = Order::where('user_id', $user->id)
                ->orderBy('created_at', 'desc')
                ->with([
                    'subOrders.OrderTrack',
                    'subOrders.OrderItems.foodItem',
                    'subOrders.chefs'
                ]);
            $orders = $query->get();
            $orders->each(function ($order) {
                $order->subOrders->each->makeVisible('customer_delivery_token');
            });

            return response()->json(["message" => "fetched user order successfully", "data" => $orders, "success" => true], 200);
        } catch (Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function getUserOrderDetails(Request $req)
    {
        // Validation
        $validator = Validator::make($req->all(), [
            'id' => 'required|integer',
            // Adjust validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors(), "success" => false], 400);
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
                return response()->json(["message" => "No orders found for this user", "success" => true], 200);
            }

            return response()->json(["data" => $data, "trackDetails" => $trackDetails, "success" => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage()); // Log as an error
            return response()->json(['message' => 'Oops! Something went wrong. Please try again.', 'success' => false], 500);
        }
    }

    public function userOrderInvoicePDF(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'id' => 'required|exists:sub_orders,id',
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => "Validation failed", "errors" => $validator->errors(), "success" => false], 400);
        }
        try {
            $data = SubOrders::with(['chefs', 'Orders', 'OrderItems.foodItem'])->findOrFail($req->id);
            Log::info('Invoice Data: ', [$data]);

            $pdf = Pdf::loadView('pdf.user-order-invoice', compact('data'));
            $pdf->getDomPDF()->setPaper('a4', 'portrait');
            return $pdf->download('customer-order-invoice.pdf');

            // return response()->json(["data" => $data, "success" => true], 200);

        } catch (\Exception $th) {
            Log::error($th);
            return response()->json(['message' => 'Oops! Something went wrong', $th->getMessage(), 'success' => false], 500);
        }
    }

    // $chefs = Chef::with('foodItems')->where('kitchen_name', 'like', "%$query%")->Where('postal_code', $postal)->get();
    // $foods = FoodItem::where('dish_name', 'like', "%$query%")->with('chef')->get();
    // if ($chefs) {
    //     $results = [];

    //     // Loop through chefs
    //     foreach ($chefs as $chef) {
    //         // Fetch Chef's All Column Data
    //         $results[] = $chef->toArray();
    //         // $results[] = [
    //         //     'id' => $chef->id,
    //         //     'firstName' => $chef->firstName,
    //         //     'lastName' => $chef->lastName,
    //         //     'kitchen_name' => $chef->kitchen_name,
    //         //     'type' => 'chef',
    //         // ];
    //     }
    //     return response()->json(['success' => true, 'message' => 'Searched by Chef respective data', 'data' => $results]);
    // }


    public function searchFood(Request $request)
    {
        try {
            $query = $request->input('query');
            $postal = $request->input('postal_code');
            if (!$postal || !$query) {
                return response()->json(['success' => true, 'message' => 'please provide the requied details', 'data' => ''], 200);
            }

            $chefs = Chef::with('foodItems')
                ->where(function ($queryBuilder) use ($query) {
                    $queryBuilder->where('kitchen_name', 'like', "%$query%")
                        ->orWhereHas('foodItems', function ($foodItemsQuery) use ($query) {
                            $foodItemsQuery->where('dish_name', 'like', "%$query%");
                        });
                })
                ->where('postal_code', $postal)
                ->get();

            $results = [];

            foreach ($chefs as $chef) {
                $matchedFoodItems = $chef->foodItems->filter(function ($foodItem) use ($query) {
                    // return strpos(strtolower($foodItem->dish_name), strtolower($query)) !== false;
                    return stripos($foodItem->dish_name, $query) !== false;
                })->toArray();

                if (!empty($matchedFoodItems) || stripos($chef->kitchen_name, $query) !== false) {
                    $results[] = [
                        'chef_id' => $chef->id,
                        'kitchen_name' => $chef->kitchen_name,
                        'food_item' => $matchedFoodItems,
                    ];
                }
            }
            return response()->json(['success' => true, 'message' => 'Search data fetch successfully', 'data' => $results], 200);
        } catch (Exception $e) {
            // Return error response in case of any exception
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function changePassword(Request $request)
    {
        // Validate the request
        Log::info($request->all());
        $validator = Validator::make($request->all(), [
            'mobile' => 'required|string',
            'new_password' => 'required|string|min:8',
            'confirmed' => 'required|same:new_password',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()->first()], 422);
        }

        try {
            // Retrieve the user by mobile number
            $user = User::where('mobile', $request->mobile)->first();
            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }
            // Update the user's password
            $user->password = Hash::make($request->new_password);
            Log::info($user);
            $user->save();

            return response()->json([
                'success' => true,
                'message' => 'Password changed successfully!',
            ], 200);
        } catch (Exception $e) {
            Log::error($e);
            return response()->json([
                'success' => false,
                'message' => 'Oops! Something went wrong, please try again!',
            ], 500);
        }
    }

    public function getPostalCode(Request $req)
    {
        $user = auth()->guard('user')->user();

        if ($user) {
            return response()->json([
                'postal_code' => $user->postal_code,
                'message' => 'Password changed successfully!',
            ], 200);
        } else {
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }
    }


    public function updatePostalCode(Request $req)
    {
        try {
            $user = auth()->guard('user')->user();
            $update = [
                'postal_code' => $req->postal_code,
            ];
            User::where('id', $user->id)->update($update);
            return response()->json([
                'message' => 'Postal Code Updated successfully!',
            ], 200);

        } catch (Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json([
                'message' => 'User not found.',
            ], 404);
        }
    }

    public function calculateDistanceForChefs(Request $req){
        $userPostalCode = $req->user_postal_code;
        $chefDetails = $req->chef_details;
        $userLatLong = $this->get_lat_long($userPostalCode);
        $lat1 = $userLatLong['lat'];
        $long1 = $userLatLong['long'];

        $chefDistances = [];

        $adminDetails = Adminsetting::first(['base_price', 'min_shipping_charges']);
        $base_price = $adminDetails->base_price;
        $minShip = $adminDetails->min_shipping_charges;
        foreach($chefDetails as $chef){

            $latLong = $this->get_lat_long($chef['postal_code']);
            $lat2 = $latLong['lat'];
            $long2 = $latLong['long'];

            $chefDistance = $this->calculateDistance($lat1, $long1, $lat2, $long2);
            $mainShipPrice = $chefDistance * $base_price;

            if($mainShipPrice < $minShip){
                $mainShipPrice = $minShip;
            }

            $chefDistances[] = [
                'chef_name' => $chef['name'],
                'shipping_charge' => $mainShipPrice
            ];
        }

        return $chefDistances;
    }

    private function calculateDistance($lat1, $lon1, $lat2, $lon2, $unit = "K") {
        $theta = $lon1 - $lon2;
        $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                cos(num: deg2rad($theta));
        $dist = acos($dist);
        $dist = rad2deg($dist);
        $miles = $dist * 60 * 1.1515;
    
        if ($unit == "K") {
            $distance = $miles * 1.609344;
        } elseif ($unit == "N") {
            $distance = $miles * 0.8684;
        } else {
            $distance = $miles;
        }
    
        return round($distance, 2); 
    }
}
