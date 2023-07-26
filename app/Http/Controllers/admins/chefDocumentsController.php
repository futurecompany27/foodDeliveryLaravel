<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
use App\Models\chef;
use App\Models\State;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class chefDocumentsController extends Controller
{
    function addDocumentItemNameAccToChefType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'state_id' => 'required',
            'doc_item_name' => 'bail|required',
            'shef_type' => 'required'
        ], [
            'state_id.required' => 'Please select a state',
            'shef_type.required' => 'Please select a shef type',
            'doc_item_name.required' => 'Document item name is required',
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            DocumentItemList::create([
                //all these fields has to be mass assignable;
                'state_id' => $req->state_id,
                'document_item_name' => strtolower($req->doc_item_name),
                'reference_links' => $req->reference_link,
                'additional_links' => $req->additional_link,
                'detail_information' => htmlspecialchars($req->detail_info),
                'chef_type' => trim($req->shef_type)
            ]);
            DB::commit();
            return response()->json(["message" => "added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateDocumentItemNameAccToChefType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            if ($req->state_id) {
                $updateData['state_id'] = $req->state_id;
            }
            if ($req->doc_item_name) {
                $updateData['document_item_name'] =  strtolower($req->doc_item_name);
            }
            if ($req->reference_link) {
                $updateData['reference_links'] = $req->reference_link;
            }
            if ($req->additional_link) {
                $updateData['additional_links'] = $req->additional_link;
            }
            if ($req->detail_info) {
                $updateData['detail_information'] =  htmlspecialchars($req->detail_info);
            }
            if ($req->shef_type) {
                $updateData['chef_type'] =  $req->shef_type;
            }
            DocumentItemList::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteDocumentItemNameAccToChefType(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            DocumentItemList::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    function getDocumentListAccToChefType(Request $req)
    {
        try {
            $totalRecords = DocumentItemList::count();
            $skip = $req->page * 10;
            $data = DocumentItemList::skip($skip)->take(10)->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function addDynamicFieldsForChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'doc_item_id' => 'required',
            'name' => 'bail|required',
            'type' => 'required',

        ], [
            "doc_item_id.required" => "Please select doc item id",
            'name.required' => 'Please enter the Field name',
            'type.required' => 'Please select the type'
        ]);

        /*------- if has error----------*/
        if ($validator->fails()) {
            return response()->json(['message' => validator()->errors(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            DocumentItemField::insert([
                'document_item_list_id' => $req->doc_item_id,
                'field_name' => strtolower($req->name),
                'type' => $req->type,
                'mandatory' => isset($req->mandatory) ? $req->mandatory : 0,
                'allows_as_kitchen_name' => isset($req->allow_as_kitchen) ? $req->allow_as_kitchen : 0,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(['message' => 'Document item Field created Successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateDynamicFieldsForChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            Log::info($req);
            if ($req->doc_item_id) {
                $updateData['document_item_list_id'] = $req->doc_item_id;
            }
            if ($req->name) {
                $updateData['field_name'] =  strtolower($req->name);
            }
            if ($req->type) {
                $updateData['type'] = $req->type;
            }
            if ($req->mandatory == "0" || $req->mandatory == "1") {
                $updateData['mandatory'] = isset($req->mandatory) ? $req->mandatory : 0;
            }
            if ($req->allow_as_kitchen == "0" || $req->allow_as_kitchen == "1") {
                $updateData['allows_as_kitchen_name'] =  isset($req->allow_as_kitchen) ? $req->allow_as_kitchen : 0;
            }
            DocumentItemField::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteDynamicFieldsForChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            DocumentItemField::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function getDynamicFieldsForChef(Request $req)
    {
        try {
            $totalRecords = DocumentItemField::count();
            $skip = $req->page * 10;
            $data = DocumentItemField::skip($skip)->take(10)->get();
            return response()->json([
                'data' => $data,
                'TotalRecords' => $totalRecords,
            ]);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }
}
