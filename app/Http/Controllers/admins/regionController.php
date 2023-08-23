<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Http\Controllers\utility\commonFunctions;
use App\Models\City;
use App\Models\Country;
use App\Models\Pincode;
use App\Models\PostalCode;
use App\Models\State;
use Database\Seeders\CountrySeeder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class regionController extends Controller
{
    function addCountry(Request $req)
    {
        if (!$req->name || !$req->country_code) {
            return response()->json(['message' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $countryExist = Country::where("name", $req->name)->first();
            if ($countryExist) {
                return response()->json(["message" => 'Country is already exist!', "success" => false], 400);
            }
            $country = new Country;
            $country->name = $req->name;
            $country->country_code = $req->country_code;
            $country->save();
            DB::commit();
            return response()->json(["message" => "country added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateCountry(Request $req)
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
            $updateData = $req->all();
            Country::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deleteCountry(Request $req)
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
            Country::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getCountry(Request $req)
    {
        try {
            return response()->json(['data' => Country::all(), "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function addState(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'country_id' => 'required',
            'name' => 'required',
            'tax_type' => 'required',
            'tax_value' => 'required'
        ], [
            'country_id.required' => 'Please select a country',
            'name.required' => 'Please mention name of sate',
            'tax_type.required' => 'Atleast one tax is required',
            "tax_value" => 'Tax value is required'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $stateExist = State::where("name", $req->name)->first();
            if ($stateExist) {
                return response()->json(["message" => 'State is already exist!', "success" => false], 400);
            }
            $state = new State;
            $state->country_id = $req->country_id;
            $state->name = $req->name;
            $state->tax_type = $req->tax_type;
            $state->tax_value = $req->tax_value;
            $state->save();
            DB::commit();
            return response()->json(["message" => "State added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updateState(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        if ($req->country_id) {
            $updateData['country_id'] = $req->country_id;
        }
        if ($req->name) {
            $updateData['name'] = $req->name;
        }
        if ($req->tax_type) {
            $updateData['tax_type'] = $req->tax_type;
        }
        if ($req->tax_value) {
            $updateData['tax_value'] = $req->tax_value;
        }
        try {
            State::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deleteState(Request $req)
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
            $data = State::where('id', $req->id)->first();
            State::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getState(Request $req)
    {
        try {
            if ($req->country_id) {
                $data = State::where('country_id', $req->country_id)->get();
            } else {
                $data = State::with('country')->get();
            }
            return response()->json(['data' => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function addCity(Request $req)
    {
        if (!$req->name || !$req->state_id) {
            return response()->json(['message' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $cityExist = City::where("name", $req->name)->first();
            if ($cityExist) {
                return response()->json(["message" => 'City is already exist!', "success" => false], 400);
            }
            $City = new City;
            $City->name = $req->name;
            $City->state_id = $req->state_id;
            $City->save();
            DB::commit();
            return response()->json(["message" => "City added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateCity(Request $req)
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
            $data = City::where('id', $req->id)->first();
            $updateData = $req->all();
            City::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deleteCity(Request $req)
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
            $data = City::where('id', $req->id)->first();
            City::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getCity(Request $req)
    {

        try {
            if ($req->state_id) {
                $data = City::where('state_id', $req->state_id)->get();
            } else {
                $data = City::with([
                    'state' => function ($query) {
                        $query->select('id', 'name', 'country_id');
                    },
                    'state.country' => function ($query) {
                        $query->select('id', 'name');
                    }
                ])->get();
            }
            return response()->json(['data' => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function addPincode(Request $req)
    {
        if (!$req->pincode || !$req->city_id) {
            return response()->json(['message' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $pincodeExist = Pincode::where("pincode", $req->pincode)->first();
            if ($pincodeExist) {
                return response()->json(["message" => 'Pincode is already exist!', "success" => false], 400);
            }
            $Pincode = new Pincode;
            $Pincode->pincode = str_replace(" ", "", (strtolower($req->pincode)));
            $Pincode->city_id = $req->city_id;
            $Pincode->latitude = $req->lat;
            $Pincode->longitude = $req->long;
            $Pincode->save();
            DB::commit();
            return response()->json(["message" => "pincode added successfully ", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function updatePincode(Request $req)
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
            $updateData = [];
            $updateData['pincode'] = str_replace(" ", "", (strtolower($req->pincode)));
            $updateData['city_id'] = $req->city_id;
            $updateData['latitude'] = $req->lat;
            $updateData['longitude'] = $req->long;
            Pincode::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function deletePincode(Request $req)
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
            Pincode::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getPincode(Request $req)
    {
        try {
            if ($req->city_id) {
                $data = Pincode::where('city_id',$req->city_id)->with([
                    'city' => function ($query) {
                        $query->select('id', 'state_id');
                    },
                    'city.state' => function ($query) {
                        $query->select('id', 'country_id');
                    }
                ])->get();
            }else{
                $data = Pincode::with([
                    'city' => function ($query) {
                        $query->select('id', 'state_id');
                    },
                    'city.state' => function ($query) {
                        $query->select('id', 'country_id');
                    }
                ])->get();
            }
            return response()->json(['data' => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateCountryStatus(Request $req)
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
            Country::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateStateStatus(Request $req)
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
            State::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function updateCityStatus(Request $req)
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
            City::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }
}
