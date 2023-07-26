<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Mail\HomeshefUserEmailVerificationMail;
use App\Models\NoRecordFound;
use App\Models\PaymentCredentialsCardData;
use App\Models\PaymentCredentialsPayPalData;
use App\Models\ShippingAddresse;
use App\Models\User;
use App\Models\chef;
use App\Models\ChefReview;
use App\Models\UserContact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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

    function getChefsByPostalCode(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'postal_code' => "required",
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->all(), 'success' => false], 400);
        }

        try {
            $totalRecords = UserContact::count();
            $skip = $req->page * 10;
            $items = chef::skip($skip)->take(10)->get();

            return response()->json([
                'data' => $items,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getChefDetails(Request $req)
    {
        if (!$req->chef_id) {
            return response()->json(['message' => 'please fill all th required fields', "success" => false], 400);
        }
        try {
            $data = chef::find($req->chef_id);
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
                $user->save();
                $userDetail = User::find($user->id);
                Mail::to(trim($req->email))->send(new HomeshefUserEmailVerificationMail($userDetail));
                DB::commit();
                return response()->json(['message' => 'Register successfully!', "data" => $userDetail, 'success' => true], 201);
            }
        } catch (\Throwable $th) {
            Log::info($th);
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
            $newData = new NoRecordFound();
            if ($req->user_id) {
                $userDetail = User::find($req->user_id);
                $ifSameData = NoRecordFound::where(['postal_code' => $req->postal_code, 'email' => $userDetail->email])->first();
                if (!$ifSameData) {
                    $newData->postal_code = $req->postal_code;
                    $newData->full_name = $userDetail->fullname;
                    $newData->email = $userDetail->email;
                    $newData->save();
                }
            } else {
                $ifSameData = NoRecordFound::where(['postal_code' => $req->postal_code, 'email' => $req->email])->first();
                if (!$ifSameData) {
                    $newData->postal_code = $req->postal_code;
                    $newData->full_name = $req->fullname;
                    $newData->email = $req->email;
                    $newData->save();
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
            $recordToUpdate = User::where('id', $req->user_id)->update(['fullname' => $req->fullname, 'email' => $req->email, 'mobile' => str_replace('-', '', $req->mobile)]);
            if ($recordToUpdate) {
                return response()->json(['message' => 'User updated successfully', 'success' => true], 200);
            } else {
                return response()->json(['message' => 'User not found', 'success' => false], 404);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again!', 'success' => false], 500);
        }
    }

    public function ChefReview(Request $req)
    {

        $validator = Validator::make(
            $req->all(),
            [
                "chef_id" => 'required',
                "images" => 'required',
                "star_rating" => "required|integer|min:1|max:5",
                "message" => 'required',
            ],
            [
                "chef_id.required" => "please fill chef_id",
                "images.required" => "please select images",
                "star_rating.required" => "please select star_rating",
                "message.required" => "please fill message",
            ]
        );
        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        if ($req->hasFile('images')) {
            $images = $req->file('images');
            $imagePaths = [];
            foreach ($images as $image) {
                $imagePath = $image->store('chef_reviews/' . $req->chef_id . '/', "public");
                array_push($imagePaths, asset('storage/' . $imagePath));
            }
        }
        try {
            $review = new ChefReview();
            $review->full_name = isset($req->full_name) ? $req->full_name : 'anonymous';
            $review->chef_id = $req->chef_id;
            $review->images = json_encode($imagePaths); //Encode Array into String to store it in database
            $review->star_rating = $req->star_rating;
            $review->message = $req->message;
            $review->save();
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
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $data = ChefReview::where('id', $req->id)->first();
            $images = json_decode($data->images);

            foreach ($images as $image) {
                str_replace('http://127.0.0.1:8000/', '', $image);
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $image))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $image));
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
        try {
            $data = ChefReview::where('chef_id', $req->chef_id)->get();
            foreach ($data as $value) {
                $value->images = json_decode($value->images);
            }
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try again !' . $th->getMessage(), 'success' => false], 500);
        }
    }
}