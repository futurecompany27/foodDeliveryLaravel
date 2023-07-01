<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Http\Controllers\utility\commonFunctions;
use App\Models\City;
use App\Models\Country;
use App\Models\Pincode;
use App\Models\State;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class regionController extends Controller
{
    function addCountry(Request $req)
    {
        if (!$req->name || !$req->country_code) {
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $country = new Country;
            $country->name = $req->name;
            $country->country_code = $req->country_code;
            $country->save();
            DB::commit();
            return response()->json(["msg" => "country added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false],500);
        }
    }

    function addState(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'country_id' => 'required',
            'name' => 'required',
            'tax_type.*' => 'required',
            'tax_value.*' => 'required'
        ], [
            'country_id.required' => 'Please select a country',
            'name.required' => 'Please mention name of sate',
            'tax_type.required' => 'Atleast one tax is required',
            "tax_value" => 'Tax value is required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $state = new State;
            $state->name = $req->name;
            $state->country_id = $req->country_id;
            $state->tax_type = json_encode($req->tax_type);
            $state->tax_value = json_encode($req->tax_value);
            $state->save();
            DB::commit();
            return response()->json(["msg" => "State added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }

    function addCity(Request $req)
    {
        if (!$req->name || !$req->state_id) {
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $City = new City;
            $City->name = $req->name;
            $City->state_id = $req->state_id;
            $City->save();
            DB::commit();
            return response()->json(["msg" => "City added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false],500);
        }
    }

    function addPincode(Request $req)
    {
        if (!$req->pincode || !$req->city_id) {
            return response()->json(['error' => 'please fill all the fields', 'success' => false], 400);
        }
        try {
            DB::beginTransaction();
            $Pincode = new Pincode;
            $Pincode->pincode = str_replace(" ", "", (strtolower($req->pincode)));
            $Pincode->city_id = $req->city_id;

            $commonFunctions = new commonFunctions;
            $lat_long = $commonFunctions->get_lat_long(str_replace(" ", "", (strtolower($req->postal_code))));
            // log::info($lat_long);
            // $Pincode->latitude = $lat_long['lat'];
            // $Pincode->longitude = $lat_long['long'];

            // $Pincode->latitude = 45.618200;
            // $Pincode->longitude = -73.797240;
            $Pincode->save();
            DB::commit();
            return response()->json(["msg" => "pincode added successfully ", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }
}