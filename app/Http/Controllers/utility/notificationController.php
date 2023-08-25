<?php

namespace App\Http\Controllers\utility;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                $notifiable_type = 'App\Models\chef';
            }
            $notifications = DB::table('notifications')->orderBy('created_at', 'desc')->where(['notifiable_type' => $notifiable_type, 'notifiable_id' => $req->id])->whereNull('read_at')->get();
            return response()->json(["data" => $notifications, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
}