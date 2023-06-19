<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\ShefSubType;
use App\Models\ShefType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class shefTypesController extends Controller
{
    function addShefType(Request $req)
    {
        $validatedData = Validator::make($req->all(), [
            'name' => 'required|unique:shef_types|min:4',
        ], [
            'name.required' => 'Shef type is required',
            'name.unique' => 'Shef type already exists. Try another type name',
            'name.min' => 'Tag name should be atleast 4 letters longs',
        ]);
        if ($validatedData->fails()) {
            return response()->json(['error' => $validatedData->errors(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            ShefType::create([
                'name' => ucfirst($req->name),
            ]);
            DB::commit();

            return response()->json(['msg' => 'Shef type created successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }

    }

    function getAllShefTypes() {
        try {
            $data = ShefType::all();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !' . $th->getMessage(), 'success' => false]);
        }
    }

    function addShefSubType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'type' => 'required',
            'name' => 'bail|required|unique:shef_subtypes',
        ], [
            'name.required' => 'Please enter the Subtype',
            'name.unique' => 'Subtype already exists'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            ShefSubType::insert([
                'type_id' => $req->type,
                'name' => ucfirst($req->name),
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(['msg' => 'Shef subtype Added Successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !'. $th->getMessage(), 'success' => false]);
        }
    }

    function getAllShefSubTypes() {
        try {
            $data = ShefSubType::all();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !' . $th->getMessage(), 'success' => false]);
        }
    }
}