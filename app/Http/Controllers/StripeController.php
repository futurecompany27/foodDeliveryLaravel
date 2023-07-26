<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class StripeController extends Controller
{
    public function makePayment(Request $request)
    {
        // Set your Stripe secret key here
        Stripe::setApiKey(env('STRIPE_SECRET_KEY'));

        // Get the necessary data from the frontend
        $token = $request->input('stripeToken');
        $amount = 1000; // Replace with the amount you want to charge (in cents)

        try {
            // Create the charge
            Charge::create([
                'amount' => $amount,
                'currency' => 'cad',
                // Change to your desired currency
                'source' => $token,
            ]);

            // Payment succeeded
            return response()->json(['message' => 'Payment successful', 'success' => true], 200);
        } catch (\Exception $e) {
            // Payment failed
            return response()->json(['error' => $e->getMessage(), 'success' => false], 500);
        }
    }
}