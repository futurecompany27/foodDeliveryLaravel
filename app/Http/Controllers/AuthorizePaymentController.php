<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use net\authorize\api\contract\v1 as AnetAPI;
use net\authorize\api\controller as AnetController;
use App\Http\Controllers\Controller;
use App\Mail\OrderPlacedMailToUser;
use App\Models\Admin;
use App\Models\Cart;
use App\Models\Chef;
use App\Models\Order;
use App\Models\OrderItems;
use App\Models\OrderTrackDetails;
use App\Models\SubOrders;
use App\Models\User;
use App\Notifications\admin\newOrderPlacedForAdmin;
use App\Notifications\Chef\newOrderPlacedForChef;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use PhpParser\Error;

class AuthorizePaymentController extends Controller
{
    public function paymentTest()
    {
        return response()->json(['message' => 'Welcome to Payment', 'success' => true], 200);
    }

    public function createAnAcceptPaymentTransaction(Request $laravelRequest)
    {

        $user = $laravelRequest->user();

        $user_type = $laravelRequest->user_type;

        $opaqueData = $laravelRequest->opaqueData;
        $amount = $laravelRequest->amount;
        $txn_type = $laravelRequest->transaction_type;
        $txn_status = 'paid';
        $txn_remark = $laravelRequest->remark;

        $orderData = $laravelRequest->order_data;

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $refId = 'ref' . time();

        $opaquePayment = new AnetAPI\OpaqueDataType();
        $opaquePayment->setDataDescriptor($opaqueData['dataDescriptor']);
        $opaquePayment->setDataValue($opaqueData['dataValue']);

        $paymentType = new AnetAPI\PaymentType();
        $paymentType->setOpaqueData($opaquePayment);

        $customerData = new AnetAPI\CustomerDataType();
        $customerData->setId($user->id);
        $customerData->setEmail($user->email);

        $transactionRequest = new AnetAPI\TransactionRequestType();
        $transactionRequest->setTransactionType("authCaptureTransaction");
        $transactionRequest->setAmount($amount);
        $transactionRequest->setPayment($paymentType);
        $transactionRequest->setCustomer($customerData);

        $apiRequest = new AnetAPI\CreateTransactionRequest();
        $apiRequest->setMerchantAuthentication($merchantAuthentication);
        $apiRequest->setRefId($refId);
        $apiRequest->setTransactionRequest($transactionRequest);

        $controller = new AnetController\CreateTransactionController($apiRequest);
        $response = $controller->executeWithApiResponse(\net\authorize\api\constants\ANetEnvironment::SANDBOX);

        if ($response !== null && $response->getMessages()->getResultCode() === "Ok") {
            $tresponse = $response->getTransactionResponse();
            if ($tresponse !== null && $tresponse->getResponseCode() === "1") {
                
                // Order data will only come for customer, or else for chef certificate no order will be generated
                if($orderData){
                    $txn_type = Transaction::TYPE_ORDER;
                    $this->addOrder($user ,$orderData, $tresponse->getTransId());
                }

                // Adding transaction
                $this->addTransaction($txn_type, $user_type, $user->id, $txn_remark, $txn_status, $amount, $tresponse->getTransId());
                return response()->json([
                    'success' => true,
                    'transaction_id' => $tresponse->getTransId(),
                    'auth_code' => $tresponse->getAuthCode(),
                    'message' => $tresponse->getMessages()[0]->getDescription(),
                    'tresponse' => $tresponse
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $tresponse ? $tresponse->getErrors()[0]->getErrorText() : 'Transaction failed.',
                ], 400);
            }
        } else {
            $message = $response?->getMessages()?->getMessage()[0]?->getText() ?? 'Unknown error';
            return response()->json(['success' => false, 'error' => $message], 400);
        }
    }

    public function paypalTransaction(Request $laravelRequest){

        $user = $laravelRequest->user();

        if(!$laravelRequest->amount){
            throw new Error("Please provide payment");
        }

        $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
        $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
        $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

        $refId = uniqid('REF-');

        $payPalType = new AnetAPI\PayPalType();
        $payPalType->setSuccessUrl(env('FRONTEND_DOMAIN') );
        $payPalType->setCancelUrl(env('FRONTEND_DOMAIN'));

        $paymentOne = new AnetAPI\PaymentType();
        $paymentOne->setPayPal($payPalType);

        $transactionRequestType = new AnetAPI\TransactionRequestType();
		$transactionRequestType->setTransactionType( "authCaptureTransaction");
		$transactionRequestType->setPayment($paymentOne);
		$transactionRequestType->setAmount(30);
        
		$request = new AnetAPI\CreateTransactionRequest();
		$request->setMerchantAuthentication($merchantAuthentication);
		$request->setTransactionRequest( $transactionRequestType);
        $request->setRefId( $refId);
		$controller = new AnetController\CreateTransactionController($request);
		$response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);
        // return  $response->getTransactionResponse();
        if ($response != null) {
            if ($response->getMessages()->getResultCode() == "Ok") {
                $tresponse = $response->getTransactionResponse();
                Cache::put('paypal_payment_' . $user->id, $tresponse->getTransId(), now()->addMinutes(1440)); // store cache in 24 hr

                //Returning the response url
                return $tresponse->getSecureAcceptance();

            } else {
                echo "Transaction Failed \n";
                $tresponse = $response->getTransactionResponse();


                if ($tresponse != null && $tresponse->getErrors() != null) {
                    return $tresponse->getErrors();
                } 
                return $response;
            }
        } else {
            echo  "No response returned \n";
        }
    }

    public function checkPaymentStatus(Request $laravelRequest)
{
    $user = $laravelRequest->user();
    $transId = Cache::get('paypal_payment_' . $user->id);

    $merchantAuthentication = new AnetAPI\MerchantAuthenticationType();
    $merchantAuthentication->setName(env('AUTHORIZE_LOGIN_ID'));
    $merchantAuthentication->setTransactionKey(env('AUTHORIZE_TRANSACTION_KEY'));

    $refId = 'ref' . time();

    //create a transaction of type get details
    $transactionRequestType = new AnetAPI\TransactionRequestType();
    $transactionRequestType->setTransactionType( "getDetailsTransaction"); 
    
    //replace following transaction ID with your transaction ID for which the details are required
    $transactionRequestType->setRefTransId($transId);

    // Create the payment data for a paypal account
    $payPalType = new AnetAPI\PayPalType();
    $payPalType->setCancelUrl(env('FRONTEND_DOMAIN'));
    $payPalType->setSuccessUrl(env('FRONTEND_DOMAIN'));
    $paymentOne = new AnetAPI\PaymentType();
    $paymentOne->setPayPal($payPalType);

    $transactionRequestType->setPayment($paymentOne);

    //create a transaction request
    $request = new AnetAPI\CreateTransactionRequest();
    $request->setMerchantAuthentication($merchantAuthentication);
    $request->setRefId( $refId);
    $request->setTransactionRequest( $transactionRequestType);

    $controller = new AnetController\CreateTransactionController($request);

    //execute the api call to get transaction details
    $response = $controller->executeWithApiResponse( \net\authorize\api\constants\ANetEnvironment::SANDBOX);

    if ($response !== null && $response->getMessages()->getResultCode() == "Ok") {
        $transaction = $response->getTransactionResponse();
        Cache::delete('paypal_payment_' . $user->id);
        return $transaction;
        // return response()->json([
        //     'status' => $transaction->getTransactionStatus(), // e.g., "settledSuccessfully", "capturedPendingSettlement"
        //     'amount' => $transaction->getAuthAmount(),
        //     'paymentMethod' => $transaction->getPayment()->getPayPal() ? "PayPal" : "Other"
        // ]);
    } else {
        return response()->json([
            'error' => $response->getMessages()->getMessage()[0]->getText()
        ], 422);
    }
}

    private function addTransaction($transaction_type, $user_type, $user_id, $remark, $status, $amount, $tx_no)
    {
        $transaction = new Transaction();
        $transaction->user_type = $user_type;
        $transaction->transaction_type = $transaction_type;
        $transaction->user_id = $user_id;
        $transaction->remark = $remark;
        $transaction->status = $status;
        $transaction->amount = $amount;
        $transaction->txn_no = $tx_no;
        $transaction->save();

        return $transaction;
    }

    private function addOrder($user, $req, $transaction_id)
    {
        $validator = Validator::make($req, [
            "user_id" => 'required',
        ], [
            "user_id.required" => "Please fill user_id",
        ]);

        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }

        try {
            DB::beginTransaction();

            if (Order::where('transacton_id', $transaction_id)->exists()) {
                return response()->json(['success' => true, "message" => "Your order already placed", 'data' => ''], 200);
            }

            // Insert order and get order ID
            $ID = Order::insertGetId([
                'order_total' => $req['order_total'],
                'tax_types' => json_encode($req['tax_types']),
                'order_tax' => round($req['order_tax'], 2),
                'order_date' => Carbon::now(),
                'grand_total' => round($req['grand_total'], 2),
                'user_id' => $req['user_id'],
                'shipping_address' => $req['shipping_address'],
                'postal_code' => $req['postal_code'],
                'latitude' => $req['latitude'],
                'longitude' => $req['longitude'],
                'city' => $req['city'],
                'state' => $req['state'],
                'delivery_date' => $req['delivery_date'],
                'delivery_time' => $req['delivery_time'],
                'total_order_item' => $req['total_order_item'],
                'tip_total' => round($req['tip_total'], 2),
                'payment_mode' => $req['payment_mode'],
                'food_instruction' => $req['food_instruction'],
                'delivery_option' => $req['delivery_option'],
                'option_desc' => $req['option_desc'],
                'delivery_instructions' => $req['delivery_instructions'],
                'payment_status' => $req['payment_status'],
                'transacton_id' => $transaction_id,
                'user_mobile_no' => str_replace("-", "", $req['user_mobile_no']),
                'username' => $req['username'],
                'created_at' => Carbon::now(),
            ]);

            // Generate order ID and update the order record
            $orderID = '#HP' . str_pad($ID, 8, '0', STR_PAD_LEFT);
            Order::where('id', $ID)->update(['order_id' => $orderID, 'updated_at' => Carbon::now()]);

            $user = User::find($req['user_id']);
            $cartData = json_decode($req['cartData'], true);

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
                'grand_total' => $req['grand_total'],
                'total_order_item' => $req['total_order_item'],
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
            foreach ($req['sub_order_tax'] as $subOrderTax) {
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
                    Log::info('2', [$req['driver_tax']]);
                    // Add driver commission and taxes
                    if (isset($req['driver_tax'])) {
                        foreach ($req['driver_tax'] as $driverTax) {
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
                    if ($req['payment_status'] == 'paid') {
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
            if ($req['payment_status'] == "paid" || $req['payment_status'] == "Paid") {
                Cart::where('user_id', $req['user_id'])->delete();
            }
            DB::commit();
            return true;
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            throw $th;
        }
    }



    
}
