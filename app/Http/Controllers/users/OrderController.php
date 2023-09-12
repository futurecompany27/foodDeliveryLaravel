<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    function placeOrders(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
        ], [
            "user_id.required" => "please fill user_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $Order = new Order;
            $Order->tax_types = $req->tax_types;
            $Order->order_total = $req->order_total;
            $Order->order_tax = $req->order_tax;
            $Order->grand_total = $req->grand_total;
            $Order->user_id = $req->user_id;
            $Order->shipping_address = $req->shipping_address;
            $Order->postal_code = $req->postal_code;
            $Order->city = $req->city;
            $Order->state = $req->state;
            $Order->delivery_date = $req->delivery_date;
            $Order->delivery_time = $req->delivery_time;
            $Order->total_order_item = $req->total_order_item;
            $Order->tip_total = $req->tip_total;
            $Order->payment_mode = $req->payment_mode;

            $ID = $Order->insertGetId();
            $orderID = ('#HP' . (1000 + $ID));
            Order::where('id', $ID)->update(['order_number' => $orderID]);

            return response()->json(['message' => 'Order placed successfully', 'success' => true], 200);

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }
}