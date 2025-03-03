<?php

namespace App\Http\Controllers\admins;

use App\Http\Controllers\Controller;
use App\Models\Kitchentype;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
// use Intervention\Image\Image; //Intervention Image
use Intervention\Image\ImageManagerStatic as Image;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class kitchentypeController extends Controller
{
    public function addKitchenTypes(Request $req)
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
            DB::beginTransaction();

            // $filename = null; // Default value if no image is uploaded

            if ($req->hasFile('image')) {
                $big_image = $req->file('image');

                $path = "cuisine";

                // Resize Image using Intervention
                $img = Image::make($big_image)
                    ->resize(200, 200)
                    ->encode($big_image->getClientOriginalExtension());

                // Upload Image to S3
                Storage::disk('s3')->put($path, (string) $img, 'public');

                $storedPath = $req->file('image')->store($path, 's3');
                Storage::disk('s3')->setVisibility($storedPath, 'public');
            }

            Kitchentype::create([
                'kitchentype' => strtolower($req->kitchentype),
                'image' =>  Storage::disk('s3')->url($storedPath),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
            return response()->json(["message" => "Added successfully", "success" => true], 201);
        } catch (\Exception $th) {
            Log::error("S3 Upload Error: " . $th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }




    public function getKitchenTypes(Request $req)
    {
        try {
            // Define a cache key
            $cacheKey = 'kitchen_types_' . ($req->admin ? 'admin' : 'user');

            // Check if the data is cached
            $kitchenTyepData = Cache::get($cacheKey);

            // If data is not cached, query the database and cache it
            if (!$kitchenTyepData) {
                if ($req->admin) {
                    $kitchenTyepData = Kitchentype::all();
                } else {
                    $kitchenTyepData = Kitchentype::where('status', 1)->get();
                }

                // Store the data in the cache for 30 minutes (or adjust as needed)
                Cache::put($cacheKey, $kitchenTyepData, now()->addMinutes(30));
            }

            return response()->json(["data" => $kitchenTyepData, "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function updateKitchenTypes(Request $req)
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
            $data = Kitchentype::where('id', $req->id)->first();
            $updateData = [];

            if ($req->kitchentype) {
                $updateData['kitchentype'] = $req->kitchentype;
            }

            if ($req->hasFile('image')) {
                // Delete Old Image from S3 (if exists)
                if (!empty($data->image)) {
                    $oldImagePath = str_replace(env('filePath'), '', $data->image);
                    if (Storage::disk('s3')->exists($oldImagePath)) {
                        Storage::disk('s3')->delete($oldImagePath);
                    }
                }

                $big_image = $req->file('image');

                $path = "cuisine";

                // Resize Image using Intervention
                $img = Image::make($big_image)
                    ->resize(200, 200)
                    ->encode($big_image->getClientOriginalExtension());

                // Upload Image to S3
                Storage::disk('s3')->put($path, (string) $img, 'public');

                $storedPath = $req->file('image')->store($path, 's3');
                Storage::disk('s3')->setVisibility($storedPath, 'public');


                // Update Image Path
                $updateData['image'] =Storage::disk('s3')->url($storedPath);
            }

            Kitchentype::where('id', $req->id)->update($updateData);

            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function deleteKitchenTypes(Request $req)
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
            $data = Kitchentype::where('id', $req->id)->first();
            $images = $data->image;
            str_replace(env('filePath'), '', $images);
            if (file_exists(str_replace(env('filePath'), '', $images))) {
                unlink(str_replace(env('filePath'), '', $images));
            }
            Kitchentype::where('id', $req->id)->delete();
            return response()->json(['message' => 'Deleted successfully', "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong', 'success' => false], 500);
        }
    }

    public function updateKitchentypeStatus(Request $req)
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
            Kitchentype::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
