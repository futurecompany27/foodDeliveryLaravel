<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Mail\HomeshefCustomerEmailVerifiedSuccessfully;
use App\Mail\HomeshefUserEmailVerificationMail;
use App\Models\Admin;
use App\Models\NoRecordFound;
use App\Models\PaymentCredentialsCardData;
use App\Models\PaymentCredentialsPayPalData;
use App\Models\ShippingAddresse;
use App\Models\User;
use App\Models\chef;
use App\Models\ChefReview;
use App\Models\Pincode;
use App\Models\UserContact;
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
            Log::info($th);
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

    public function updateUserDetailStatus(Request $req)
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
        Log::info("//////");
        $validator = Validator::make($req->all(), [
            'postal_code' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
        }

        try {
            $serviceExist = Pincode::where('pincode', $req->postal_code)->where('status', 1)->first();
            if ($serviceExist) {
                if ($req->filter == 'true') {
                    $minPrice = $req->input('min');
                    if ($req->input('max') > 300) {
                        $maxPrice = 99999999999999999;
                    } else {
                        $maxPrice = $req->input('max');
                    }
                    $skip = $req->page * 12;
                    $query = chef::where('postal_code', strtolower($req->postal_code))->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1);
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

                    Log::info($skip);
                    $total = $query->count();
                    $data = $query->skip($skip)->limit(12)->get();
                    return response()->json(['data' => $data, 'total' => $total, 'success' => true], 200);
                } else {
                    $query = chef::where('postal_code', strtolower($req->postal_code))->whereJsonContains('chefAvailibilityWeek', $req->todaysWeekDay)->where('chefAvailibilityStatus', 1)->whereHas('foodItems', function ($query) use ($req) {
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
            'landmark' => "required",
            'locality' => "required",
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
                $newAdrees->landmark = $req->postal_code;
                $newAdrees->locality = $req->locality;
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

    public function addUserContacts(Request $req)
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

    public function updateContactStatus(Request $req)
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

    public function getUserContact(Request $req)
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

    public function ChefReview(Request $req)
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
        $imagePaths = [];
        if ($req->hasFile('images')) {
            $images = $req->file('images');
            foreach ($images as $image) {
                $imagePath = $image->store('chef_reviews/' . $req->chef_id . '/', "public");
                array_push($imagePaths, asset('storage/' . $imagePath));
            }
        }
        try {
            $reviewExist = ChefReview::where('user_id', $req->user_id)->where('chef_id', $req->chef_id)->first();
            if ($reviewExist) {
                $images = isset($reviewExist->images) ? json_decode($reviewExist->images) : [];
                foreach ($images as $value) {
                    Log::info($value);
                    Log::info(str_replace(env('filePath'), '', $value));
                    if (file_exists(str_replace(env('filePath'), '', $value))) {
                        unlink(str_replace(env('filePath'), '', $value));
                    }
                }

                $update = [
                    'star_rating' => $req->star_rating,
                    "message" => $req->message
                ];
                if (count($imagePaths) > 0) {
                    $update['images'] = json_encode($imagePaths);
                } else {
                    $update['images'] = json_encode([]);
                }
                ChefReview::where('user_id', $req->user_id)->where('chef_id', $req->chef_id)->update($update);
            } else {
                $review = new ChefReview();
                $review->user_id = $req->user_id;
                $review->chef_id = $req->chef_id;
                $review->star_rating = $req->star_rating;
                $review->message = $req->message;
                if (count($imagePaths) > 0) {
                    $review->images = json_encode($imagePaths); //Encode Array into String to store it in database
                }
                $review->save();
            }

            $reviewDetails = ChefReview::orderBy('created_at', 'desc')->with(['user', 'chef'])->where(['user_id' => $req->user_id, 'chef_id' => $req->chef_id])->first();
            $reviewDetails['date'] = Carbon::now();
            $chef = chef::find($req->chef_id);
            $chef->notify(new NewChefReviewNotification($reviewDetails));
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new NewReviewNotification($reviewDetails));
            }
            $allReview = ChefReview::select('star_rating')->where('chef_id', $req->chef_id)->get();
            $totalNoReview = ChefReview::where('chef_id', $req->chef_id)->count();
            $totalStars = 0;
            foreach ($allReview as $value) {
                $totalStars = $totalStars + $value['star_rating'];
            }
            $rating = $totalStars / $totalNoReview;
            chef::where('id', $req->chef_id)->update(['rating' => $rating]);
            return response()->json(['message' => "Submitted successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function deleteChefReview(Request $req)
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
            $data = ChefReview::where('id', $req->id)->first();
            $images = json_decode($data->images);

            foreach ($images as $image) {
                str_replace(env('filePath'), '', $image);
                if (file_exists(str_replace(env('filePath'), '', $image))) {
                    unlink(str_replace(env('filePath'), '', $image));
                }
            }
            ChefReview::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    public function getChefReview(Request $req)
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
            $totalRecords = ChefReview::where('chef_id', $req->chef_id)->count();
            $skip = $req->page * 10;
            $data = ChefReview::where('chef_id', $req->chef_id)->skip($skip)->take(10)->with('user:fullname,id')->get();
            foreach ($data as $value) {
                $value->images = json_decode($value->images);
            }
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getCountOftheChefAvailableForNext14Days(Request $req)
    {
        $dateList = [];

        for ($i = 1; $i <= 14; $i++) {
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
                'weekday' => $dayName,
                'weekdayShort' => $weekdayShort,
                'formatted_date' => $date->format('M d'),
                'iso_date' => $date->toDateString(),
            ];
        }

        // getting counts of the available shefs for next 14 days
        foreach ($dateList as &$val) {
            $query = chef::where('postal_code', strtolower($req->postal_code));
            $query->where('chefAvailibilityStatus', 1)->whereJsonContains('chefAvailibilityWeek', $val['weekdayShort'])->whereHas('foodItems', function ($query) use ($val) {
                $query->whereJsonContains('foodAvailibiltyOnWeekdays', $val['weekdayShort']);
            });
            $val['total'] = $query->count();
        }

        return response()->json(['data' => $dateList, 'success' => true], 200);
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
}