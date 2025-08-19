<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Mail\allSubOrderAcceptedMail;
use App\Mail\OrderPlacedMailToUser;
use App\Mail\subOrderDeclineMail;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Chef;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\OrderTrackDetails;
use App\Models\SubOrders;
use App\Models\User;
use App\Models\Adminsetting;
use App\Models\Transaction;
use App\Notifications\admin\newOrderPlacedForAdmin;
use App\Notifications\Chef\newOrderPlacedForChef;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class OrderController extends Controller
{

    function placeOrders(Request $req)
    {
        $validator = Validator::make($req->all(), [
            "user_id" => 'required',
        ], [
            "user_id.required" => "Please fill user_id",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            if (Order::where('transacton_id', $req->transacton_id)->exists()) {
                return response()->json(['success' => true, "message" => "Your order already placed", 'data' => ''], 200);
            }

            // Insert order and get order ID
            $ID = Order::insertGetId([
                'order_total' => $req->order_total,
                'tax_types' => json_encode($req->tax_types),
                'order_tax' => round($req->order_tax, 2),
                'order_date' => Carbon::now(),
                'grand_total' => round($req->grand_total, 2),
                'user_id' => $req->user_id,
                'shipping_address' => $req->shipping_address,
                'postal_code' => $req->postal_code,
                'latitude' => $req->latitude,
                'longitude' => $req->longitude,
                'city' => $req->city,
                'state' => $req->state,
                'delivery_date' => $req->delivery_date,
                'delivery_time' => $req->delivery_time,
                'total_order_item' => $req->total_order_item,
                'tip_total' => round($req->tip_total, 2),
                'payment_mode' => $req->payment_mode,
                'food_instruction' => $req->food_instruction,
                'delivery_option' => $req->delivery_option,
                'option_desc' => $req->option_desc,
                'delivery_instructions' => $req->delivery_instructions,
                'payment_status' => $req->payment_status,
                'transacton_id' => $req->transacton_id,
                'user_mobile_no' => str_replace("-", "", $req->user_mobile_no),
                'username' => $req->username,
                'created_at' => Carbon::now(),
            ]);

            // Generate order ID and update the order record
            $orderID = '#HP' . str_pad($ID, 8, '0', STR_PAD_LEFT);
            Order::where('id', $ID)->update(['order_id' => $orderID, 'updated_at' => Carbon::now()]);

            $user = User::find($req->user_id);
            $cartData = json_decode($req->cartData, true);

            // Collect dish names for email
            $dishNames = [];
            foreach ($cartData as $cart) {
                foreach ($cart['foodItems'] as $foodItem) {
                    $dishNames[] = $foodItem['dish_name'];
                }
            }

            // Order details for email/notifications
            $orderDetails = [
                'order_id' => $orderID,
                'userName' => $user->firstName . ' ' . $user->lastName,
                'dishNames' => $dishNames,
                'grand_total' => $req->grand_total,
                'total_order_item' => $req->total_order_item,
                'created_at' => Carbon::now()->format('d-F-Y h:i:s A')
            ];

            // Send email if enabled
            try {
                if (config('services.is_mail_enable')) {
                    Mail::to(trim($user->email))->send(new OrderPlacedMailToUser($orderDetails));
                }
            } catch (\Exception $e) {
                Log::error($e->getMessage());
            }

            // Notify admins of the new order
            $admins = Admin::all();
            foreach ($admins as $admin) {
                $admin->notify(new newOrderPlacedForAdmin($orderDetails));
            }

            // Process sub-orders per chef
            foreach ($req->sub_order_tax as $subOrderTax) {
                // Filter out the cart data for the current chef
                $chefCartData = array_filter($cartData, function ($cart) use ($subOrderTax) {
                    return $cart['chef_id'] == $subOrderTax['chef_id'];
                });
                // Ensure only one sub-order per chef
                foreach ($chefCartData as $card_value) {
                    $foodItems = $card_value['foodItems'];
                    $amount = 0;

                    // Calculate amount for the chef's suborder
                    foreach ($foodItems as $food) {
                        $amount += ($food['price'] * $food['quantity']);
                    }
                    Log::info('1');

                    // Insert sub-order
                    $add = [
                        'order_id' => $orderID,
                        'chef_id' => $subOrderTax['chef_id'],
                        'item_total' => count($foodItems),
                        'amount' => $amount,
                        'created_at' => Carbon::now(),
                        'chef_commission' => $subOrderTax['commission_percentage'],
                        'chef_commission_amount' => $subOrderTax['commission_amount'],
                        'chef_commission_taxes' => isset($subOrderTax['commission_tax']) ? json_encode($subOrderTax['commission_tax']) : null,
                        'sub_order_tax_detail' => isset($subOrderTax['suborder_tax']) ? json_encode($subOrderTax['suborder_tax']) : null,
                    ];
                    Log::info('2', [$req->driver_tax]);
                    // Add driver commission and taxes
                    if (isset($req->driver_tax)) {
                        foreach ($req->driver_tax as $driverTax) {
                            // Assuming the driver tax corresponds to each suborder
                            $add['driver_commission'] = $driverTax['commission_percentage'];
                            $add['driver_commission_amount'] = $driverTax['commission_amount'];
                            $add['driver_commission_taxes'] = isset($driverTax['commission_tax']) ? json_encode($driverTax['commission_tax']) : null;
                        }
                    }
                    Log::info('Card_Value', [$card_value]);
                    // Handle tips
                    $tip = $card_value['tip'] ?? 'noTip';
                    $fixTip = $card_value['fixedTip'] ?? 0;
                    if ($tip == 'fixedAmount') {
                        $add['tip'] = $tip;
                        $add['tip_type'] = 'Fixed';
                    } elseif ($tip == 'noTip') {
                        $add['tip'] = 0;
                        $add['tip_type'] = 'No Tip';
                    } else {
                        $add['tip'] = $tip;
                        $add['tip_type'] = 'Percentage';
                    }
                    $add['tip_amount'] = $fixTip;

                    // Insert sub-order and generate sub-order ID
                    $sub_id = SubOrders::insertGetId($add);
                    $subOrderID = '#HPSUB' . str_pad($sub_id, 8, '0', STR_PAD_LEFT);

                    // Notify the chef about the sub-order
                    $chefDetail = Chef::findOrFail($subOrderTax['chef_id']);
                    $customerName = ucfirst(strtolower($user->firstName)) . ' ' . ucfirst(strtolower($user->lastName));
                    $subOrderDetail = [
                        'sub_order_id' => $subOrderID,
                        'userName' => $customerName
                    ];
                    $chefDetail->notify(new newOrderPlacedForChef($subOrderDetail));

                    // Order tracking details if payment is paid
                    if ($req->payment_status == 'paid') {
                        $track_id = OrderTrackDetails::insertGetId([
                            'status' => 'Order Placed',
                            'track_desc' => 'Order Placed - Waiting for chef confirmation',
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now()
                        ]);

                        $orderTrackingID = '#TRACK' . str_pad($track_id, 8, '0', STR_PAD_LEFT);

                        OrderTrackDetails::where('id', $track_id)->update([
                            'track_id' => $orderTrackingID,
                            'track_desc' => 'Order date: ' . Carbon::now() . ' -> Waiting for chef confirmation',
                            'created_at' => Carbon::now()
                        ]);

                        $customerToken = SubOrders::generateUniqueCustomerDeliveryToken();
                        $pickupToken = SubOrders::generateUniquerPickupToken();
                        SubOrders::where('id', $sub_id)->update([
                            'pickup_token' => $pickupToken,
                            'customer_delivery_token' => $customerToken,
                            'sub_order_id' => $subOrderID,
                            'status' => '2',
                            'track_id' => $orderTrackingID,
                            'updated_at' => Carbon::now()
                        ]);
                    } else {
                        SubOrders::where('id', $sub_id)->update(['status' => '10', 'sub_order_id' => $subOrderID, 'updated_at' => Carbon::now()]);
                    }

                    // Insert order items for each sub-order
                    foreach ($foodItems as $food) {
                        OrderItems::insert([
                            'sub_order_id' => $subOrderID,
                            'food_id' => $food['food_id'],
                            'quantity' => $food['quantity'],
                            'price' => $food['price'],
                            'total' => $food['quantity'] * $food['price'],
                            'created_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            // Clear the cart if payment is completed
            if ($req->payment_status == "paid" || $req->payment_status == "Paid") {
                Cart::where('user_id', $req->user_id)->delete();
            }
            DB::commit();
            return response()->json(['message' => 'Order placed successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['error' => $th->getMessage(), 'message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }



    public function calculate_tax(Request $req)
    {
        // Decode and validate inputs
        $cartData = json_decode($req->cart_data);
        if ($cartData === null) {
            return response()->json(['error' => 'Invalid cart data'], 400);
        }

        $tax_types = json_decode($req->tax_type, true);
        if (
            $tax_types === null ||
            !isset($tax_types['tax_type']) ||
            !isset($tax_types['tax_value'])
        ) {
            return response()->json(['error' => 'Invalid tax data'], 400);
        }

        // Initialize accumulators
        $total_taxable_amount   = 0;
        $all_chef_taxes_detail  = [];
        $all_driver_taxes_detail = [];
        $order_tax              = [];

        // Fetch admin settings once
        $adminsetting = AdminSetting::first();

        // Loop through each chef’s cart segment
        foreach ($cartData as $card_value) {
            $foodItems = $card_value->foodItems;
            $amount    = 0;
            $chef      = Chef::find($card_value->chef_id);
            $is_taxable = (bool)$chef->is_tax_document_completed;

            // 1) Compute subtotal for this chef
            foreach ($foodItems as $food) {
                $amount += $food->price * $food->quantity;
            }

            // 2) Commission percentages
            $areYouA = $chef->are_you_a;
            if (!empty($areYouA)) {
                $field = Str::lower(str_replace(' ', '', $areYouA)) . '_comm';
                $commission_percentage = $adminsetting->$field ?? $adminsetting->default_comm;
            } else {
                $commission_percentage = $adminsetting->default_comm;
            }
            $driver_commission_percentage = $adminsetting->default_comm;

            // 3) Commission amounts
            $chef_commission_amount   = round($amount * $commission_percentage   / 100, 2);
            $driver_commission_amount = round($amount * $driver_commission_percentage / 100, 2);

            // 4) Build per‑tax arrays
            $suborder_taxes       = [];
            $chef_commission_taxes   = [];
            $driver_commission_taxes = [];

            foreach ($tax_types['tax_type'] as $i => $type) {
                $rate = $tax_types['tax_value'][$i];

                // a) Chef commission tax
                if($is_taxable == true) {
                    $chefTaxAmt = round($chef_commission_amount * ($rate / 100), 2);
                } else {
                    $chefTaxAmt = 0;
                }
                $chef_commission_taxes[] = [
                    $type   => $rate,
                    'Amount'=> $chefTaxAmt,
                ];

                // b) Driver commission tax
                $driverTaxAmt = round($driver_commission_amount * ($rate / 100), 2);
                $driver_commission_taxes[] = [
                    $type   => $rate,
                    'Amount'=> $driverTaxAmt,
                ];

                // c) Suborder tax (only if chef is taxable)
                $subTaxAmt = $is_taxable
                    ? round($amount * ($rate / 100), 2)
                    : 0;
                $suborder_taxes[] = [
                    $type   => $rate,
                    'Amount'=> $subTaxAmt,
                ];
            }

            // 5) If chef is taxable, add to total taxable base
            if ($is_taxable) {
                $total_taxable_amount += $amount;
            }

            // 6) Collect this chef’s detail
            $all_chef_taxes_detail[] = [
                'chef_id'            => $card_value->chef_id,
                'suborder_tax'       => $suborder_taxes,
                'commission_tax'     => $chef_commission_taxes,
                'commission_amount'  => $chef_commission_amount,
                'commission_percentage'=> $commission_percentage,
                'is_tax_applicable'  => $is_taxable ? 1 : 0,
                'foods_total'        => $amount,
            ];

            // 7) Collect driver detail
            $all_driver_taxes_detail[] = [
                'commission_tax'      => $driver_commission_taxes,
                'commission_amount'   => $driver_commission_amount,
                'commission_percentage'=> $driver_commission_percentage,
            ];
        }

        // 8) Build order‑level tax array
        foreach ($tax_types['tax_type'] as $i => $type) {
            $rate = $tax_types['tax_value'][$i];
            $amt  = $total_taxable_amount > 0
                ? round($total_taxable_amount * ($rate / 100), 2)
                : 0;

            $order_tax[] = [
                $type   => $rate,
                'Amount'=> $amt,
            ];
        }

        // 9) Prepare response payload
        $data = [
            'tax_type'      => $tax_types,
            'total_tax'     => $total_taxable_amount > 0
                                ? round($total_taxable_amount * array_sum($tax_types['tax_value']) / 100, 2)
                                : 0,
            'order_tax'     => $order_tax,
            'sub_order_tax' => $all_chef_taxes_detail,
            'driver_tax'    => $all_driver_taxes_detail,
        ];

        return response()->json([
            'message' => 'Calculation done',
            'data'    => $data,
            'success' => true
        ], 200);
    }



    function acceptOrRejectOrder(Request $req)
    {
        Log::info($req);
        $track_status = [
            'Accepted' => 'Your request has been successfully approved and processed',
            'Rejected' => 'Unfortunately, your request has been declined after review',
            'Pending' => 'Your request is currently under review; please wait patiently'

        ];
        $description = $track_status[$req->status];
        Log::info($description);
        $validator = Validator::make($req->all(), [
            "sub_order_id" => 'required',
            "status" => 'required',
        ], [
            "sub_order_id.required" => "Please fill sub_order_id",
            "status.required" => "Please fill status",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $subOrder = SubOrders::where(['sub_order_id' => $req->sub_order_id])->with(['Orders.user', 'chefs'])->first();
            $update = ['status' => $req->status];
            if ($req->reason) {
                $update['reason'] = $req->reason;
            }
            SubOrders::where(['sub_order_id' => $req->sub_order_id])->update($update);

            OrderTrackDetails::create([
                'track_id' => $subOrder->track_id,
                'status' => $req->status,
                'track_desc' => $description,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now()
            ]);


            // OrderTrackDetails::where(['track_id' => $subOrder->track_id])->update(['status' => $req->status]);
            $mail = ['userName' => ($subOrder->orders->user->firstName . ' ' . $subOrder->orders->user->lastName), 'status' => $req->status, 'chefName' => ($subOrder->chefs->firstName . ' ' . $subOrder->chefs->lastName), 'order_id' => $subOrder->order_id];
            if ($req->status == 'Rejected') {
                try {
                    if (config('services.is_mail_enable')) {
                        Mail::to(trim($subOrder->orders->user->email))->send(new subOrderDeclineMail($mail));
                    }
                } catch (\Exception $e) {
                    Log::error($e);
                }
            } else {
                $order = Order::where('order_id', $subOrder->order_id)->with('subOrders')->first();
                $allAccepted = $order->subOrders->every(function ($suborder) {
                    return $suborder->status === 'Accepted';
                });
                if ($allAccepted) {
                    try {
                        if (config('services.is_mail_enable')) {
                            Mail::to(trim($subOrder->orders->user->email))->send(new allSubOrderAcceptedMail($mail));
                        }
                    } catch (\Exception $e) {
                        Log::error($e);
                    }
                }
            }

            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    function trackOrder(Request $req)
    {
        try {
            // Validate tracking Id
            $validator = Validator::make($req->all(), [
                "track_id" => 'required|exists:sub_orders,track_id',
            ], [
                "track_id.required" => "Please fill track id",
            ]);
            if ($validator->fails()) {
                return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
            }
            // Find order by ID
            $order_track_detail = OrderTrackDetails::where('track_id', $req->track_id)->get()->toArray();
            if (!$order_track_detail) {
                return response()->json(['message' => 'Invalid track ID'], 404);
            }
            // // Implement tracking logic (modify according to your needs)
            // $data = [ // Replace with actual tracking data retrieval
            //     'track_id' => $order->track_id,
            //     'status' => $order->status,
            //     'status' => $order->status,
            //     'created_at' => $order->created_at, // Example, replace if needed
            //     'updated_at' => $order->updated_at,
            // ];

            return response()->json(["message" => 'Order tracking information retrieved successfully', 'data' => $order_track_detail, 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::error($th->getMessage());
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }


    public function getAllTransactions(Request $request)
    {
        if ($request->has('user_type', 'user_id')) {
            $userId = $request->user_id;
            $userType = $request->user_type;
            if ($userType === 'chef') {
                $transactions = Transaction::where('user_type', 'chef')
                    ->where('user_id', $userId)->orderBy('created_at', 'desc')->get();

                $transactionData = $this->mapTransactions($transactions);

                $totalCount = Transaction::where('user_type', 'chef')
                    ->where('user_id', $userId)
                    ->count();

                return response()->json(['success' => true, 'message' => 'data fetched', 'total' => $totalCount, 'data' => $transactionData], 200);
            } elseif ($userType === 'driver') {
                $transactions = Transaction::where('user_type', 'driver')
                    ->where('user_id', $userId)->orderBy('created_at', 'desc')->get();
                $totalCount = Transaction::where('user_type', 'driver')
                    ->where('user_id', $userId)
                    ->count();
                return response()->json(['success' => true, 'message' => 'data fetched', 'total' => $totalCount, 'data' => $transactions], 200);
            } else {
                return response()->json(['message' => 'please fill the user id and user type', 'success' => false, 'data' => ''], 400);
            }
        }
        // $transactions = Transaction::orderBy('created_at', 'desc')->get();
        $transactions = Transaction::with(['chef:id,firstName,lastName' ?? 'driver:id,firstName,lastName'])->orderBy('created_at', 'desc')->get();
        $totalcount = Transaction::count();

        $transactionData = $this->mapTransactions($transactions);

        return response()->json([
            'success' => true,
            'message' => 'Transactions fetched successfully.',
            'total' => $totalcount,
            'data' => $transactionData
        ], 200);
    }

    private function mapTransactions($transactions){
        $mappedTransactions = $transactions->map(function ($transaction) {
            $data = $transaction->toArray();

            $data['transaction_type'] = match ($transaction->transaction_type) {
                Transaction::TYPE_ORDER => Transaction::$types[Transaction::TYPE_ORDER],
                Transaction::TYPE_HANDLER_CERTIFICATE => Transaction::$types[Transaction::TYPE_HANDLER_CERTIFICATE],
                Transaction::TYPE_LICENSE_CERTIFICATE => Transaction::$types[Transaction::TYPE_LICENSE_CERTIFICATE],
                default => 'Unknown',
            };

            return $data;
        });
        return $mappedTransactions;
    }

    /**
     * Get comprehensive order summary data for frontend
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderSummaryData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_id' => 'required|string',
        ], [
            'order_id.required' => 'Order ID is required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'success' => false
            ], 400);
        }

        try {
            $order = Order::where('order_id', $req->order_id)->first();
            
            if (!$order) {
                return response()->json([
                    'message' => 'Order not found', 
                    'success' => false
                ], 404);
            }

            $orderSummaryData = $order->generateOrderSummaryData();

            return response()->json([
                'message' => 'Order summary data retrieved successfully',
                'success' => true,
                'data' => $orderSummaryData
            ], 200);

        } catch (\Exception $th) {
            Log::error('Error getting order summary data: ' . $th->getMessage());
            return response()->json([
                'message' => 'Something went wrong while retrieving order summary data',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get order summary data for multiple orders
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMultipleOrdersSummaryData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order_ids' => 'required|array',
            'order_ids.*' => 'string',
        ], [
            'order_ids.required' => 'Order IDs array is required',
            'order_ids.array' => 'Order IDs must be an array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'success' => false
            ], 400);
        }

        try {
            $orders = Order::whereIn('order_id', $req->order_ids)->get();
            
            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found', 
                    'success' => false
                ], 404);
            }

            $ordersSummaryData = [];
            foreach ($orders as $order) {
                $ordersSummaryData[] = $order->generateOrderSummaryData();
            }

            return response()->json([
                'message' => 'Orders summary data retrieved successfully',
                'success' => true,
                'data' => $ordersSummaryData
            ], 200);

        } catch (\Exception $th) {
            Log::error('Error getting multiple orders summary data: ' . $th->getMessage());
            return response()->json([
                'message' => 'Something went wrong while retrieving orders summary data',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Get all orders summary data with pagination
     * 
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllOrdersSummaryData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'status' => 'nullable|string',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ], [
            'page.integer' => 'Page must be a number',
            'page.min' => 'Page must be at least 1',
            'per_page.integer' => 'Per page must be a number',
            'per_page.min' => 'Per page must be at least 1',
            'per_page.max' => 'Per page cannot exceed 100',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => $validator->errors()->first(), 
                'success' => false
            ], 400);
        }

        try {
            $page = $req->page ?? 1;
            $perPage = $req->per_page ?? 10;
            
            // Build query
            $query = Order::with(['subOrders.chefs', 'subOrders.driver', 'user']);
            
            // Apply filters
            if ($req->status) {
                $query->where('payment_status', $req->status);
            }
            
            if ($req->date_from) {
                $query->whereDate('order_date', '>=', $req->date_from);
            }
            
            if ($req->date_to) {
                $query->whereDate('order_date', '<=', $req->date_to);
            }
            
            // Get paginated orders
            $orders = $query->orderBy('order_date', 'desc')
                           ->paginate($perPage, ['*'], 'page', $page);
            
            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'No orders found', 
                    'success' => false
                ], 404);
            }

            // Generate summary data for each order
            $ordersSummaryData = [];
            foreach ($orders as $order) {
                $ordersSummaryData[] = $order->generateOrderSummaryData();
            }

            // Calculate totals for all orders in current page
            $pageTotals = $this->calculatePageTotals($ordersSummaryData);

            return response()->json([
                'message' => 'Orders summary data retrieved successfully',
                'success' => true,
                'data' => [
                    'orders' => $ordersSummaryData,
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                        'has_more_pages' => $orders->hasMorePages(),
                    ],
                    'page_totals' => $pageTotals,
                    'filters_applied' => [
                        'status' => $req->status,
                        'date_from' => $req->date_from,
                        'date_to' => $req->date_to,
                    ]
                ]
            ], 200);

        } catch (\Exception $th) {
            Log::error('Error getting all orders summary data: ' . $th->getMessage());
            return response()->json([
                'message' => 'Something went wrong while retrieving orders summary data',
                'success' => false,
                'error' => $th->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate totals for all orders in a page
     * 
     * @param array $ordersSummaryData
     * @return array
     */
    private function calculatePageTotals($ordersSummaryData)
    {
        $totals = [
            'total_orders' => count($ordersSummaryData),
            'total_order_amount' => 0,
            'total_commission_for_all_chefs' => 0,
            'total_tax_amount_for_all_chef_gst' => 0,
            'total_tax_amount_for_all_chef_qst' => 0,
            'total_commission_from_chef' => 0,
            'total_commission_for_driver' => 0,
            'total_tax_amount_for_driver_gst' => 0,
            'total_tax_amount_for_driver_qst' => 0,
            'total_commission_from_driver' => 0,
            'total_service_charges_from_all_chef' => 0,
            'total_service_charges_for_driver_gst' => 0,
            'total_service_charges_for_driver_qst' => 0,
            'total_service_charges_from_chef' => 0,
            'total_service_charges_from_drivers' => 0,
            'total_service_charges_for_chef_tps' => 0,
            'total_service_charges_for_chef_qst' => 0,
            'total_service_charges_from_drivers' => 0,
            'total_chef_earning' => 0,
            'total_driver_earning' => 0,
            'total_admin_earning' => 0,
        ];

        foreach ($ordersSummaryData as $orderData) {
            $summary = $orderData['summary'];
            
            $totals['total_order_amount'] += $orderData['order_amount'];
            $totals['total_commission_for_all_chefs'] += $summary['commission_for_all_chefs'];
            $totals['total_tax_amount_for_all_chef_gst'] += $summary['tax_amount_for_all_chef_gst'];
            $totals['total_tax_amount_for_all_chef_qst'] += $summary['tax_amount_for_all_chef_qst'];
            $totals['total_commission_from_chef'] += $summary['total_commission_from_chef'];
            $totals['total_commission_for_driver'] += $summary['commission_for_driver'];
            $totals['total_tax_amount_for_driver_gst'] += $summary['tax_amount_for_driver_gst'];
            $totals['total_tax_amount_for_driver_qst'] += $summary['tax_amount_for_driver_qst'];
            $totals['total_commission_from_driver'] += $summary['total_commission_from_driver'];
            $totals['total_service_charges_from_all_chef'] += $summary['service_charges_from_all_chef'];
            $totals['total_service_charges_for_driver_gst'] += $summary['service_charges_for_driver_gst'];
            $totals['total_service_charges_for_driver_qst'] += $summary['service_charges_for_driver_qst'];
            $totals['total_service_charges_from_chef'] += $summary['total_service_charges_from_chef'];
            $totals['total_service_charges_from_drivers'] += $summary['service_charges_from_drivers'];
            $totals['total_service_charges_for_chef_tps'] += $summary['service_charges_for_chef_tps'];
            $totals['total_service_charges_for_chef_qst'] += $summary['service_charges_for_chef_qst'];
            $totals['total_service_charges_from_drivers'] += $summary['total_service_charges_from_drivers'];
            $totals['total_chef_earning'] += $summary['total_chef_earning'];
            $totals['total_driver_earning'] += $summary['total_driver_earning'];
            $totals['total_admin_earning'] += $summary['total_admin_earning'];
        }

        // Round all totals to 2 decimal places
        foreach ($totals as $key => $value) {
            if ($key !== 'total_orders') {
                $totals[$key] = round($value, 2);
            }
        }

        return $totals;
    }
}
