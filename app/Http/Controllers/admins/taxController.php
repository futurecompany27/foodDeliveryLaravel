<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
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
            return response()->json(['error' => $validatedData->errors(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();

            Tax::create([
                'tax_type' => strtoupper($req->tax_type),
            ]);
            DB::commit();
            return response()->json(['msg' => "added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
}