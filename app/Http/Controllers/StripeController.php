<?php

namespace App\Http\Controllers;

use App\Mail\TransactionMail;
use App\Models\Admin;
use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Adminsetting;
use App\Models\Chef;
use App\Models\Driver;
use App\Models\Transaction;
use App\Notifications\admin\TransactionNotification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;
use Stripe\Customer;
use Stripe\EphemeralKey;


class StripeController extends Controller
{
    public function createSession(Request $req)
    {
        if (!$req->totalAmt) {
            return response()->json(["message" => 'Please fill cartData', "success" => false], 400);
        }
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'cad',
                            'product_data' => [
                                'name' => "Total",
                            ],
                            'unit_amount' => $req->totalAmt * 100,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                'customer_email' => $req->email,
                'success_url' => env("domain") . 'success-transaction',
                'cancel_url' => env("domain") . 'failed-transaction',
            ]);

            return response()->json(['id' => $session->id]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function retriveStripePaymentStatus(Request $req)
    {
        if (!$req->user_id && !$req->session_id) {
            return response()->json(["message" => 'Please fill all details', "success" => false], 400);
        }
        try {
            // Set your Stripe secret key
            Stripe::setApiKey(env('STRIPE_SECRET'));

            $session = Session::retrieve($req->session_id);
            Log::info('retriceStripePaymentStatus--------------$session');
            Log::info($session);
            return response()->json(['session' => $session, 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage(), 'success' => false], 500);
        }
    }


    public function initiatePayment(Request $request)
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET'));
        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => [[
                'price_data' => [
                    'currency' => 'cad',
                    'product_data' => [
                        'name' => $request->data['transaction_type'],
                    ],
                    'unit_amount' => $request->data['amount'] * 100,
                ],
                'quantity' => 1,
            ]],
            'metadata' => [
                'chef_id' => '1',
            ],
            'mode' => 'payment',
            'success_url' => env("domain") . 'success-transaction',
            'cancel_url' => env("domain") . 'failed-transaction',
        ]);

        return $checkout_session;
        // return response()->json(['id' => $session->id]);
    }


    public function stripeCheckout(Request $req)
    {
        if (!$req->data['amount'] && !$req->data['transaction_type'] && !$req->data['user_id'] && !$req->data['user_type'] && !$req->data['Remark']) {
            return response()->json(["message" => 'Please fill cartData', "success" => false], 400);
        }
        try {
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => 'cad',
                            'product_data' => [
                                'name' => $req->data['transaction_type'],
                                // 'name' => $req->data['transaction_type'],
                            ],
                            'unit_amount' => $req->data['amount'] * 100,
                            // 'unit_amount' => $req->data['amount'] * 100,
                        ],
                        'quantity' => 1,
                    ]
                ],
                'mode' => 'payment',
                // 'remark' => $req->data['Remark'],
                'success_url' => env("domain") . 'success-transaction-chef',
                'cancel_url' => env("domain") . 'failed-transaction',
            ]);

            Log::info('$session Log ////////');
            Log::info($session);
            Log::info('Session->id Log ///////');
            Log::info($session->id);
            return response()->json(['id' => $session->id]);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function retriveCertificatePaymentStatus(Request $req)
    {
        if (!$req->session_id) {
            return response()->json(["message" => 'Please fill all details', "success" => false], 400);
        }
        try {
            // Set your Stripe secret key
            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = Session::retrieve($req->session_id);

            return response()->json(['session' => $session, 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage(), 'success' => false], 500);
        }
    }

    public function storeTransaction(Request $req)
    {
        // Validate the incoming request
        $validator = Validator::make($req->all(), [
            "data.user_id" => 'required',
            "data.transaction_type" => 'required|string',
            "data.user_type" => 'required|string',
            "data.amount" => 'required|numeric',
            "data.payment_status" => 'nullable',
            "data.Remark" => 'nullable',
        ], [
            "data.user_id.required" => "Please fill user_id",
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            DB::beginTransaction();
            $txnData = $req->input('data', []); // Fallback to an empty array if 'data' is not set

            $txn = Transaction::create([
                'transaction_type' => $txnData['transaction_type'],
                'user_type' => $txnData['user_type'],
                'user_id' => $txnData['user_id'],
                'amount' => $txnData['amount'],
                'status' => $txnData['payment_status'],
                // 'status' => $req->payment_status,
                'remark' => $txnData['Remark'],
                'txn_no' => Transaction::generateTransactionNo(),
            ]);

            // Handle the same logic for driver....
            if ($txnData['user_type'] == 'chef') {
                $chef = Chef::find($txnData['user_id']);
                if ($chef) {
                    if (config('services.is_mail_enable')) {
                        // Send email to chef
                        Mail::to($chef->email)->send(new TransactionMail($chef, $txn));
                    }
                }
            }

            $admins = Admin::all(['*']);
            foreach ($admins as $admin) {
                $admin->notify(new TransactionNotification($chef, $txn));
            }
            DB::commit();
            return response()->json(['message' => 'Transaction stored successfully', 'data' => $txn, 'success' => true], 200);
        } catch (\Exception $th) {
            Log::error($th->getMessage());
            DB::rollback(); // Only necessary if you're starting a DB transaction
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // For Mobile
    public function paymentSheet(Request $req)
    {
        Log::info('Stripe PaymentSheet APi //', [$req->all()]);
        if (!$req->totalAmt) {
            return response()->json(["message" => 'Please fill cartData', "success" => false], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));

        try {
            // Create a new customer
            $customer = Customer::create();

            // Create an ephemeral key
            $ephemeralKey = EphemeralKey::create(
                ['customer' => $customer->id],
                ['stripe_version' => '2024-06-20']
            );

            // Create a payment intent
            $paymentIntent = PaymentIntent::create([
                'amount' => $req->totalAmt, // Amount in cents
                'currency' => 'cad',
                'customer' => $customer->id,
                'automatic_payment_methods' => ['enabled' => true],
            ]);

            return response()->json([
                'paymentIntent' => $paymentIntent->client_secret,
                'ephemeralKey' => $ephemeralKey->secret,
                'customer' => $customer->id,
                'publishableKey' => env('STRIPE_KEY'),
            ]);
        } catch (\Exception $e) {
            Log::info($e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
