<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\Kitchentype;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use File;
use Image;

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
            return response()->json(['error' => $validator->errors(), "success" => false]);
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

                $filename = "storage/admin/kitchentype/" . $name_gen;
            }
            Kitchentype::insert([
                'kitchentype' => strtolower($req->kitchentype),
                'image' => $filename,
                'created_at' => Carbon::now()->format('d-m-y h:m:i'),
                'updated_at' => Carbon::now()->format('d-m-y h:m:i')
            ]);
            DB::commit();
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => 'Oops! Something went wrong. Please try to register again !', 'success' => false]);
        }
    }
}