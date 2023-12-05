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
            'document_item_name' => 'bail|required',
            'chef_type' => 'required'
        ], [
            'state_id.required' => 'Please select a state',
            'document_item_name.required' => 'Document item name is required',
            'chef_type.required' => 'Please select a shef type',
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            DocumentItemList::create([
                //all these fields has to be mass assignable;
                'state_id' => $req->state_id,
                'document_item_name' => strtolower($req->document_item_name),
                'reference_links' => $req->reference_link,
                'additional_links' => $req->additional_links,
                'detail_information' => htmlspecialchars($req->detail_information),
                'chef_type' => trim($req->chef_type)
            ]);
            DB::commit();
            return response()->json(["message" => "Added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateDocumentItemNameAccToChefType(Request $req)
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
            if ($req->state_id) {
                $updateData['state_id'] = $req->state_id;
            }
            if ($req->document_item_name) {
                $updateData['document_item_name'] = strtolower($req->document_item_name);
            }
            if ($req->reference_links) {
                $updateData['reference_links'] = $req->reference_links;
            }
            if ($req->additional_links) {
                $updateData['additional_links'] = $req->additional_links;
            }
            if ($req->detail_information) {
                $updateData['detail_information'] = htmlspecialchars($req->detail_information);
            }
            if ($req->chef_type) {
                $updateData['chef_type'] = $req->chef_type;
            }
            DocumentItemList::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteDocumentItemNameAccToChefType(Request $req)
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
            DocumentItemList::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function getDocumentListAccToChefType(Request $req)
    {
        try {
            $data = DocumentItemList::with([
                'state' => function ($query) {
                    $query->select('id', 'name');
                },
                'shef_type' => function ($query) {
                    $query->select('id', 'name');
                }
            ])->get();
            return response()->json(['data' => $data, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateDocumentItemNameAccToChefTypeStatus(Request $req)
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
            DocumentItemList::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function addDynamicFieldsForChef(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'document_item_list_id' => 'required',
            'field_name' => 'bail|required',
            'type' => 'required',

        ], [
            "doc_item_id.required" => "Please select doc item id",
            'name.required' => 'Please enter the Field name',
            'type.required' => 'Please select the type'
        ]);

        /*------- if has error----------*/
        if ($validator->fails()) {
            return response()->json(['message' => validator()->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            DocumentItemField::insert([
                'document_item_list_id' => $req->document_item_list_id,
                'field_name' => strtolower($req->field_name),
                'type' => $req->type,
                'mandatory' => isset($req->mandatory) ? $req->mandatory : 0,
                'allows_as_kitchen_name' => isset($req->allows_as_kitchen_name) ? $req->allows_as_kitchen_name : 0,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(['message' => 'Document item Field created Successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateDynamicFieldsForChef(Request $req)
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
            if ($req->doc_item_id) {
                $updateData['document_item_list_id'] = $req->document_item_list_id;
            }
            if ($req->field_name) {
                $updateData['field_name'] = strtolower($req->field_name);
            }
            if ($req->type) {
                $updateData['type'] = $req->type;
            }
            if ($req->mandatory == "0" || $req->mandatory == "1") {
                $updateData['mandatory'] = isset($req->mandatory) ? $req->mandatory : 0;
            }
            if ($req->allows_as_kitchen_name == "0" || $req->allows_as_kitchen_name == "1") {
                $updateData['allows_as_kitchen_name'] = isset($req->allows_as_kitchen_name) ? $req->allows_as_kitchen_name : 0;
            }
            Log::info($updateData);
            DocumentItemField::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteDynamicFieldsForChef(Request $req)
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
            DocumentItemField::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function getDynamicFieldsForChef(Request $req)
    {
        try {
            $totalRecords = DocumentItemField::where('document_item_list_id', $req->document_item_list_id)->count();
            $data = DocumentItemField::where('document_item_list_id', $req->document_item_list_id)->get();
            return response()->json(['data' => $data, 'TotalRecords' => $totalRecords, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
