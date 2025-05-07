<?php

namespace App\Http\Controllers\admins;

use App\Helpers\AwsHelper;
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
    public function addKitchenTypes(Request $req)
    {
        // Validate Request
        $validator = Validator::make($req->all(), [
            'kitchentype' => 'bail|required|unique:kitchentypes',
            'image' => 'nullable|mimes:jpeg,jpg,png,svg|max:2048', // Allow null images, max 2MB
        ], [
            'kitchentype.required' => 'Please enter the kitchen type',
            'kitchentype.unique' => 'Kitchen type already exists',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors(),
                "success" => false
            ], 422);
        }

        try {
            DB::beginTransaction();

            $filename = null; // Initialize filename to handle cases where image isn't uploaded

            // Check if an image is uploaded
            if ($req->hasFile('image')) {
                $big_image = $req->file('image');

                // Generate unique file name
                $image_name = strtolower(trim($req->kitchentype));
                $new_name = str_replace(" ", "", $image_name);
                $fileName = 'kitchentype/' . time() . '_' . $new_name . '.' . $big_image->getClientOriginalExtension();

                // Log File Information
                Log::info("Uploading Image: " . $fileName);

                // AWS S3 Upload
                $s3 = AwsHelper::cred();

                if (!$s3) {
                    throw new \Exception("AWS S3 credentials not found or invalid.");
                }

                $result = $s3->putObject([
                    'Bucket' => env('AWS_BUCKET'),
                    'Key'    => $fileName,
                    'Body'   => fopen($big_image->getPathname(), 'r'),
                    'ContentType' => $big_image->getMimeType(),
                ]);


                $filename = $result['ObjectURL'];
            }

            // Insert Data
            Kitchentype::insert([
                'kitchentype' => strtolower($req->kitchentype),
                'image' => $filename, // If no image, this stays NULL
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);

            DB::commit();
            return response()->json([
                "message" => "Kitchen type added successfully",
                "success" => true
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();

            // Log full error details
            Log::error("Error adding kitchen type: " . $e->getMessage(), [
                'exception' => $e,
                'request_data' => $req->all()
            ]);

            return response()->json([
                'message' => 'Oops! Something went wrong.',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }


    function getKitchenTypes(Request $req)
    {
        try {
            if ($req->admin) {
                $kitchenTyepData = Kitchentype::all();
            } else {
                $kitchenTyepData = Kitchentype::where('status', 1)->get();
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
                if ($req->file('image') && isset($req->image)) {
                    $big_image = $req->file('image');
                    $image_name = strtolower($req->kitchentype);
                    $new_name = str_replace(" ", "", $image_name);
                    $name_gen = $new_name . "." . $big_image->getClientOriginalExtension();

                    $fileName = 'kitchentype/' . time() . '_' . $big_image->getClientOriginalName();

                    $s3 = AwsHelper::cred();

                    // Upload file to S3
                    $result = $s3->putObject([
                        'Bucket' => env('AWS_BUCKET'),
                        'Key'    => $fileName,
                        'Body'   => fopen($big_image->getPathname(), 'r'),
                        'ContentType' => $big_image->getMimeType(),
                    ]);

                    $updateData['image'] = $result['ObjectURL'] ;
                }

            }
            Kitchentype::where('id', $req->id)->update($updateData);
            return response()->json(['message' => "Updated Successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
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
