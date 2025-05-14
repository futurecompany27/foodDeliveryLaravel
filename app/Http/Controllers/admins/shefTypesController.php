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
    public function addShefType(Request $req)
    {
        $validatedData = Validator::make($req->all(), [
            'name' => 'required|unique:shef_types|min:4',
        ], [
            'name.required' => 'Chef type is required',
            'name.unique' => 'Chef type already exists. Try another type name',
            'name.min' => 'Tag name should be atleast 4 letters longs',
        ]);
        if ($validatedData->fails()) {
            return response()->json(['message' => $validatedData->errors()->first(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            ShefType::create([
                'name' => ucfirst($req->name),
            ]);
            DB::commit();

            return response()->json(['message' => 'Chef type created successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateShefType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            'name' => 'required|unique:shef_types|min:4',
        ], [
            'name.required' => 'Chef type is required',
            'name.unique' => 'Chef type already exists. Try another type name',
            'name.min' => 'Tag name should be atleast 4 letters longs',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->name) {
                $updateData['name'] = strtoupper($req->name);
            }
            $data = ShefType::where('id', $req->id)->first();
            ShefType::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteShefType(Request $req)
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
            $sheftype = ShefSubType::where('type_id', $req->id);
            if($sheftype){
                $sheftype ->delete();
                return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
            }
            return response()->json(['message' => 'ShefType not found', "success" => false], 400);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAllShefTypes()
    {
        try {
            $data = ShefType::where('status', 1)->get();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.' . $th->getMessage(), 'success' => false]);
        }
    }

    public function updateShefTypeStatus(Request $req)
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
            ShefType::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function addShefSubType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'type_id' => 'required',
            'name' => 'bail|required|unique:shef_subtypes',
        ], [
            'name.required' => 'Please enter the Subtype',
            'name.unique' => 'Subtype already exists'
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            ShefSubType::insert([
                'type_id' => $req->type_id,
                'name' => ucfirst($req->name),
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(['message' => 'Chef subtype Added Successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.' . $th->getMessage(), 'success' => false], 500);
        }
    }

    public function updateShefSubType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            'type_id' => 'required',
            'name' => 'bail|required|unique:shef_subtypes',
        ], [
            'name.required' => 'Please enter the Subtype',
            'name.unique' => 'Subtype already exists'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            if ($req->name) {
                $updateData['name'] = ucfirst($req->name);
            }
            if ($req->type_id) {
                $updateData['type_id'] = $req->type_id;
            }
            $data = ShefSubType::where('id', $req->id)->first();
            ShefSubType::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteShefSubType(Request $req)
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
            ShefSubType::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getAllShefSubTypes()
    {
        try {
            $data = ShefSubType::with('shef_type:id,name')->get();
            return response()->json(["data" => $data, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.' . $th->getMessage(), 'success' => false], 500);
        }
    }

    public function updateShefSubTypeStatus(Request $req)
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
            ShefSubType::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
