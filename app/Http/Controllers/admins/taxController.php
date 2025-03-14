<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\Pincode;
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
            return response()->json(['message' => "Added successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateTaxType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            'tax_type' => 'required|string',
        ], [
            "id.required" => "Please fill id",
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
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // public function deleteTaxType(Request $req)
    // {
    //     $validator = Validator::make($req->all(), [
    //         "id" => 'required',
    //     ], [
    //         "id.required" => "Please fill id",
    //     ]);
    //     if ($validator->fails()) {
    //         return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
    //     }
    //     try {
    //         $tax = Tax::find($req->id);
    //         Log::info($tax);
    //         $checkTax = State::where('tax_type', $tax->tax_type)->exists();
    //         Log::info($checkTax);
    //         if ($checkTax) {
    //             return response()->json(['message' => 'This entry cannot be deleted as it is in use.', "success" => true], 200);
    //         }
    //         $tax->delete();
    //         // $data = Tax::where('id', $req->id)->first();
    //         // Tax::where('id', $req->id)->delete();
    //         return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }

    public function deleteTaxType(Request $request)
    {
        $taxId = $request->input('id');
        $tax = Tax::find($taxId);
        if (!$tax) {
            return response()->json(['message' => 'Tax not found.', 'success' => false], 404);
        }
        $taxType = $tax->tax_type;
        // Check if any state uses this tax_type
        $isUsedInStates = State::whereJsonContains('tax_type', $taxType)->exists(); // For JSON column
        if ($isUsedInStates) {
            // Tax type is in use, do not delete
            return response()->json(['message' => 'You cannot delete this tax type as it is being used in states.', 'success' => false], 403);
        } else {
            // Proceed with deletion
            try {
                $tax->delete();
                return response()->json(['message' => 'Tax deleted successfully.', 'success' => true], 200);
            } catch (\Exception $th) {
                Log::info($th->getMessage());
                DB::rollback();
                return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
            }
        }
    }

    public function getTaxType(Request $req)
    {
        try {
            if ($req->postal_code) {
                $postal_codeData = Pincode::where('pincode', substr(str_replace(" ", "", strtoupper($req->postal_code)), 0, 3))->with('city.state')->first();
                $tax = ['tax_type' => $postal_codeData['city']['state']['tax_type'], 'tax_value' => $postal_codeData['city']['state']['tax_value']];
                return response()->json(['data' => $tax, 'success' => true], 200);
            } else {
                $totalRecords = Tax::count();
                $skip = $req->page * 10;
                $data = Tax::skip($skip)->take(10)->get();
                return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, 'success' => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
