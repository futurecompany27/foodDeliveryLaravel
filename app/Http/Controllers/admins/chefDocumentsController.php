<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\DocumentItemField;
use App\Models\DocumentItemList;
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
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
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
            return response()->json(["msg" => "added successfully", "success" => true], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
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
            return response()->json(['error' => validator()->errors(), "success" => false], 400);
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
            return response()->json(['msg' => 'Document item Field created Successfully', "success" => true], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }

    }
}