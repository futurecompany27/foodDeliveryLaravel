<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Checkout\Session;

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
            return response()->json(['session' => $session, 'success' => true], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage(), 'success' => false], 500);
        }
    }
}