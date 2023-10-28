<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Mail\subOrderRejectionMail;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\chef;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\OrderTrackDetails;
use App\Models\SubOrders;
use App\Models\User;
use App\Notifications\admin\newOrderPlacedForAdmin;
use App\Notifications\Chef\newOrderPlacedForChef;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
            DB::beginTransaction();
            $ID = Order::insertGetId([
                'order_total' => $req->order_total,
                'tax_types' => json_encode($req->tax_types),
                'order_tax' => $req->order_tax,
                'grand_total' => $req->grand_total,
                'user_id' => $req->user_id,
                'shipping_address' => $req->shipping_address,
                'postal_code' => $req->postal_code,
                'city' => $req->city,
                'state' => $req->state,
                'delivery_date' => $req->delivery_date,
                'delivery_time' => $req->delivery_time,
                'total_order_item' => $req->total_order_item,
                'tip_total' => $req->tip_total,
                'payment_mode' => $req->payment_mode,
                'delivery_instructions' => $req->delivery_instructions,
                'payment_status' => $req->payment_status,
                'transacton_id' => $req->transacton_id,
                'user_mobile_no' => str_replace("-", "", $req->user_mobile_no),
                'username' => $req->username,

            ]);
            $orderID = ('#HP' . str_pad($ID, 8, '0', STR_PAD_LEFT));
            Order::where('id', $ID)->update(['order_id' => $orderID]);

            $user = User::find($req->user_id);
            $orderDetails = ['order_id' => $orderID, 'userName' => $user->fullname];
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new newOrderPlacedForAdmin($orderDetails));
            }

            $cartData = json_decode($req->cartData);
            foreach ($cartData as $value) {
                $foodItems = $value->foodItems;
                $amount = 0;
                foreach ($foodItems as $food) {
                    $amount = $amount + $food->price;
                }
                $add = [
                    'order_id' => $orderID,
                    'chef_id' => $value->chef_id,
                    'item_total' => count($value->foodItems),
                    'amount' => $amount,
                ];
                if ($value->tip == 'fixedAmount') {
                    $add['tip_type'] = 'Fixed';
                } else if ($value->tip == 'noTip') {
                    $add['tip_type'] = 'No Tip';
                    $add['tip'] = 0;
                } else {
                    $add['tip'] = str_replace("%", "", $value->tip);
                    $add['tip_type'] = 'Percentage';
                }
                $add['tip_amount'] = $value->fixedTip;
                $sub_id = SubOrders::insertGetId($add);
                $subOrderID = ('#HPSUB' . str_pad($sub_id, 8, '0', STR_PAD_LEFT));

                $chef = chef::find($value->chef_id);
                $subOrderDetail = ['sub_order_id' => $subOrderID, 'userName' => $user->fullname];
                $chef->notify(new newOrderPlacedForChef($subOrderDetail));

                $track_id = OrderTrackDetails::insertGetId([]);
                $orderTrackingID = ('#TRACK' . str_pad($track_id, 8, '0', STR_PAD_LEFT));
                OrderTrackDetails::where('id', $track_id)->update(['track_id' => $orderTrackingID]);

                SubOrders::where('id', $sub_id)->update(['sub_order_id' => $subOrderID, 'track_id' => $orderTrackingID]);

                foreach ($foodItems as $food) {
                    OrderItems::insert([
                        'sub_order_id' => $subOrderID,
                        'food_id' => $food->food_id,
                        'quantity' => $food->quantity,
                        'price' => $food->price,
                        'total' => ($food->quantity * $food->price)
                    ]);
                }
            }

            if ($req->payment_status == 1) {
                Cart::where('user_id', $req->user_id)->delete();
            }
            DB::commit();
            return response()->json(['message' => 'Order placed successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

    function acceptOrRejectOrder(Request $req){
        $validator = Validator::make($req->all(), [
            "sub_order_id" => 'required',
            "status" => 'required',
        ], [
            "sub_order_id.required" => "please fill sub_order_id",
            "status.required" => "please fill sub_order_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $subOrder = SubOrders::where(['sub_order_id' =>$req->sub_order_id])->with(['Orders.user'])->first();
            SubOrders::where(['sub_order_id' =>$req->sub_order_id])->update(['status'=> $req->status]);
            if ($req->status == 'Accepted') {

            }else if ($req->status == 'Rejected'){
            OrderTrackDetails::where(['track_id'=>$subOrder->track_id])->update(['status'=> $req->status]);
            // Mail::to(trim($subOrder->orders->user->email))->send(new subOrderRejectionMail());
            }

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try to register again !', 'success' => false], 500);
        }
    }

}
