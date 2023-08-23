<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\Tax;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class taxController extends Controller
{
    function addTaxType(Request $req)
    {
        $validatedData = Validator::make($req->all(), [
            'tax_type' => 'required|string',
        ], [
            'tax_type.required' => 'Tax type is required',
            'tax_type.string' => 'Only letters allowed',
        ]);
        if ($validatedData->fails()) {
            return response()->json(['message' => $validatedData->errors(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();

            Tax::create([
                'tax_type' => strtoupper($req->tax_type),
            ]);
            DB::commit();
            return response()->json(['message' => "added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function updateTaxType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            'tax_type' => 'required|string',
        ], [
            "id.required" => "please fill id",
            'tax_type.required' => 'Tax type is required',
            'tax_type.string' => 'Only letters allowed',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->tax_type) {
                $updateData['tax_type'] = strtoupper($req->tax_type);
            }
            $data = Tax::where('id', $req->id)->first();
            Tax::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteTaxType(Request $req)
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
            $data = Tax::where('id', $req->id)->first();
            Tax::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getTaxType(Request $req)
    {
        try {
            if ($req->state) {
                $data = State::where('name',  $req->state)->first();
                return response()->json(['data' => $data, 'success' => true], 200);
            } else {
                $totalRecords = Tax::count();
                $skip = $req->page * 10;
                $data = Tax::skip($skip)->take(10)->get();
                return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }
}
