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
        if (!$req->cartData) {
            return response()->json(["message" => 'please fill cartData', "success" => false], 400);
        }
        try {

            $cartData = json_decode($req->cartData);

            $line_items = [];
            foreach ($cartData as $value) {
                $foodItems = $value->foodItems;
                foreach ($foodItems as $food) {
                    $arr = [
                        'price_data' => [
                            'currency' => 'cad',
                            'product_data' => [
                                'name' => $food->dish_name,
                                'images' => [$food->dishImage]
                            ],
                            'unit_amount' => $food->price * 100,
                        ],
                        'quantity' => $food->quantity,
                    ];
                    array_push($line_items, $arr);
                }
            }

            foreach ($cartData as $value) {
                $arr = [
                    'price_data' => [
                        'currency' => 'cad',
                        'product_data' => [
                            'name' => $value->chefName . ' tip',
                        ],
                        'unit_amount' => $value->fixedTip * 100,
                    ],
                    'quantity' => 1,
                ];
                array_push($line_items, $arr);
            }

            Stripe::setApiKey(env('STRIPE_SECRET'));
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $line_items,
                'mode' => 'payment',
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
            return response()->json(["message" => 'please fill all details', "success" => false], 400);
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