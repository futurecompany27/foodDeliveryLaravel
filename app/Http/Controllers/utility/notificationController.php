<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class notificationController extends Controller
{
    function getUnreadNotificationAccordingToUserTypes(Request $req)
    {
        if (!$req->type || !$req->id) {
            return response()->json(['message' => 'Please fil all the required fields', 'success' => true], 400);
        }
        try {
            $notifiable_type = '';
            if ($req->type == 'admin') {
                $notifiable_type = 'App\Models\Admin';
            }
            if ($req->type == 'chef') {
                $notifiable_type = 'App\Models\Chef';
            }
            $notifications = DB::table('notifications')->orderBy('created_at', 'desc')->where(['notifiable_type' => $notifiable_type, 'notifiable_id' => $req->id])->whereNull('read_at')->get();
            return response()->json(["data" => $notifications, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    public function deleteNotification(Request $req)
    {
        try {
            $notification = Notification::find($req->id);
            if (!$notification) {
                return response()->json(['message' => 'Notification not found', 'success' => false], 404);
            }
            $notification->delete();

            return response()->json(['message' => 'Notification deleted successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function markAsReadNotification(Request $request)
    {
        // Validate the incoming request
        $validator = Validator::make($request->all(), [
            'request_ids' => 'required|array|min:1|distinct|exists:notifications,id',
        ], [
            'request_ids.exists' => 'One or more provided request IDs are invalid.',
        ]);
        
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }
        try{
            
            $requestIds = $request->input('request_ids');
            
            if (empty($requestIds)) {
                return response()->json(['error' => 'No request IDs provided'], 400);
            }
            
            Notification::whereIn('id', $requestIds)
            ->update(['read_at' => now()]);
            
            return response()->json(['message' => 'Notifications marked as read successfully']);
        }
        catch (\Exception $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // public function markAsReadNotification($id)
    // {
    //     if (isset($id)) {
    //         Auth::guard('admin')->user()->notifications->where('id', $id)->markAsRead();
    //     }

    //     return back();
    // }


}
