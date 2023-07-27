<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\Kitchentype;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// use Intervention\Image\Image; //Intervention Image
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\File;

class kitchentypeController extends Controller
{
    function addKitchenTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'kitchentype' => 'bail|required|unique:kitchentypes',
            'image' => 'mimes:jpeg,jpg,png,svg',
        ], [
            'kitchentype.required' => 'Please enter the kitchen type',
            'kitchentype.unique' => 'Kitchen type already exists with us',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors(), "success" => false], 500);
        }
        try {
            $path = "storage/admin/kitchentype/";
            if (!File::exists($path)) {
                File::makeDirectory($path, $mode = 0777, true, true);
            }
            DB::beginTransaction();

            if ($req->file('image') && isset($req->image)) {

                $big_image = $req->file('image');

                $image_name = strtolower($req->kitchentype);
                $new_name = str_replace(" ", "", $image_name);
                $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                $big_img = Image::make($req->file('image'))
                    ->resize(200, 200)
                    ->save('storage/admin/kitchentype/' . $name_gen);

                $filename = asset("storage/admin/kitchentype/" . $name_gen);
            }
            Kitchentype::insert([
                'kitchentype' => strtolower($req->kitchentype),
                'image' => $filename,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
            return response()->json(["message" => "added successfully", "success" => true], 201);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function getKitchenTypes()
    {
        try {
            $kitchenTyepData = Kitchentype::all();
            foreach ($kitchenTyepData as $value) {
                $value->image = json_decode($value->image);
            }
            return response()->json(["data" => $kitchenTyepData, "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    public function updateKitchenTypes(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
        ], [
            "id.required" => "please fill id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        if (!File::exists("storage/admin/kitchentype/")) {
            File::makeDirectory("storage/admin/kitchentype/", $mode = 0777, true, true);
        }
        try {
            $data = Kitchentype::where('id', $req->id)->first();
            $updateData = [];
            if ($req->kitchentype) {
                $updateData['kitchentype'] = $req->kitchentype;
            }
            if ($req->hasFile('image')) {
                $images = $data->image;
                str_replace('http://127.0.0.1:8000/', '', $images);
                if (file_exists(str_replace('http://127.0.0.1:8000/', '', $images))) {
                    unlink(str_replace('http://127.0.0.1:8000/', '', $images));
                }
                if ($req->file('image') && isset($req->image)) {

                    $big_image = $req->file('image');
                    $image_name = strtolower($req->kitchentype);
                    $new_name = str_replace(" ", "", $image_name);
                    $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();
                    $big_img = Image::make($req->file('image'))
                        ->resize(200, 200)
                        ->save('storage/admin/kitchentype/' . $name_gen);
                    $filename = asset("storage/admin/kitchentype/" . $name_gen);
                    $updateData['image'] = $filename;
                }
            }
            Kitchentype::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }

    public function deleteKitchenTypes(Request $req)
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
            $data = Kitchentype::where('id', $req->id)->first();
            $images = $data->image;
            str_replace('http://127.0.0.1:8000/', '', $images);
            if (file_exists(str_replace('http://127.0.0.1:8000/', '', $images))) {
                unlink(str_replace('http://127.0.0.1:8000/', '', $images));
            }
            Kitchentype::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to contact again !', 'success' => false], 500);
        }
    }

    public function updateKitchentypeStatus(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "id" => 'required',
            "status" => 'required',
        ], [
            "id.required" => "please fill status",
            "status.required" => "please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors(), "success" => false], 400);
        }
        try {
            if ($req->status == "0" || $req->status == "1") {
                $updateData['status'] = $req->status;
            }
            // $updateData = $req->status;
            Kitchentype::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to update again !', 'success' => false], 500);
        }
    }
}
