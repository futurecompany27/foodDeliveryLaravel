<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\chef;
use App\Models\FoodItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class cartController extends Controller
{
    function addToCart(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => 'required',
            'cartData' => 'required',
        ], [
            'user_id.required' => 'please fill user_id',
            'cartData.required' => 'please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $mycart = Cart::where("user_id", $req->user_id)->first();
            if ($mycart) {
                Cart::where("user_id", $req->user_id)->update(['cartData' => $req->cartData]);
            } else {
                $cart = new Cart();
                $cart->user_id = $req->user_id;
                $cart->cartData = $req->cartData;
                $cart->save();
            }

            return response()->json(["message" => "added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function getMyCart(Request $req)
    {
        if (!$req->user_id) {
            return response()->json(["error" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $data = Cart::where('user_id', $req->user_id)->first();
            if ($data) {
                $myCart = $data->cartData;
                foreach ($myCart as &$chefData) {
                    $chef = chef::with('foodItems')->find($chefData['chef_id']);
                    $foodItems = $chef['foodItems'];
                    foreach ($chefData['foodItems'] as &$food) {
                        $foodItem = $foodItems->firstWhere('id', $food['food_id']);
                        if ($foodItem) {
                            $food['price'] = $foodItem['price'];
                            $food['dish_name'] = $foodItem['dish_name'];
                            $food['dishImage'] = $foodItem['dishImage'];
                        }
                    }
                }
                return response()->json(["data" => $myCart, "success" => true], 200);
            }
            return response()->json(["data" => [], "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
        }
    }

    function changeQuantity(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => 'required',
            'food_id' => 'required',
            'chef_id' => 'required',
            'type' => 'required',
            'quantity' => 'required'
        ], [
            'user_id.required' => 'please fill user_id',
            'food_id.required' => 'please fill food_id',
            'chef_id.required' => 'please fill chef_id',
            'type.required' => 'please fill type',
            'quantity.required' => 'please fill type',
        ]);
        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $cartData = Cart::where("user_id", $req->user_id)->first()->cartData;
            if ($cartData) {
                foreach ($cartData as &$cartItem) {
                    if ($cartItem['chef_id'] == $req->chef_id) {
                        foreach ($cartItem['foodItems'] as &$foodItem) {
                            if ($foodItem['food_id'] == $req->food_id) {
                                if ($req->type == 'increase') {
                                    $foodItem['quantity'] = ($foodItem['quantity'] + $req->quantity);
                                }
                                if ($req->type == 'decrease') {
                                    $foodItem['quantity'] = ($foodItem['quantity'] - $req->quantity);
                                }
                            }
                        }
                    }
                }
                Cart::where("user_id", $req->user_id)->update(["cartData" => $cartData]);
                return response()->json(["message" => "updated successfully", "success" => true], 200);
            } else {
                return response()->json(["message" => "updated successfully", "success" => true], 200);
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
        }
    }

    function removeItemFromCart(Request $req)
    {
        if (!$req->user_id || !$req->food_id || !$req->chef_id) {
            return response()->json(["error" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $cartData = Cart::where("user_id", $req->user_id)->first()->cartData;
            $food_id = $req->food_id;
            foreach ($cartData as &$value) {
                if ($value['chef_id'] == $req->chef_id) {
                    $value['foodItems'] = array_filter($value['foodItems'], function ($food) use ($food_id) {
                        return $food['food_id'] !== $food_id;
                    });
                    $value['foodItems'] = array_values($value['foodItems']);
                }
            }

            $filteredcartData = array_filter($cartData, function ($item) {
                return count($item['foodItems']) > 0;
            });

            $cartData = array_values($filteredcartData); // Re-index the collection after removing elements

            if (count($cartData) > 0) {
                Cart::where("user_id", $req->user_id)->update(["cartData" => $cartData]);
            } else {
                Cart::where("user_id", $req->user_id)->delete();
            }
            return response()->json(['msg' => 'removed successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
        }
    }
}
