<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\Cart;
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
            'chef_id' => 'required',
            'food' => 'required',
        ], [
            'user_id.required' => 'please fill user_id',
            'chef_id.required' => 'please fill chef_id',
            'food.required' => 'please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $DataExistWithSameChef = Cart::where(["user_id" => $req->user_id, 'chef_id' => $req->chef_id])->first();
            $DataExistWithDifferentChef = Cart::where(["user_id" => $req->user_id])->first();
            if ($DataExistWithSameChef) {
                $foodItemsArray = $DataExistWithSameChef->foodItems;
                $foodItemAlreadyInCart = false;
                foreach ($foodItemsArray as &$value) {
                    if ($req->food['food_id'] == $value['food_id']) {
                        $foodItemAlreadyInCart = true;
                        $value['quantity'] = $req->food['quantity'];
                    }
                }
                if (!$foodItemAlreadyInCart) {
                    array_push($foodItemsArray, $req->food);
                }
                Cart::where("user_id", $req->user_id)->update(["foodItems" => $foodItemsArray]);
            } elseif ($DataExistWithDifferentChef) {
                Cart::where("user_id", $req->user_id)->update(['chef_id' => $req->chef_id, "foodItems" => [$req->food]]);
            } else {
                $cart = new Cart();
                $cart->user_id = $req->user_id;
                $cart->chef_id = $req->chef_id;
                $cart->foodItems = [$req->food];
                $cart->save();
            }
            return response()->json(["msg" => "added successfully", "success" => true], 200);
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
            $myCart = Cart::where('user_id', $req->user_id)->first();
            if ($myCart) {
                $foodItems = $myCart->foodItems;
                foreach ($foodItems as &$value) {
                    $data = FoodItem::select('dish_name', 'dishImage', 'price')->where('id', $value['food_id'])->first();
                    $value['dish_name'] = $data->dish_name;
                    $value['dishImage'] = $data->dishImage;
                    $value['price'] = $data->price;
                }
                $myCart->foodItems = $foodItems;
            }
            return response()->json(["data" => $myCart, "success" => true], 200);
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
            'type' => 'required',
            'quantity' => 'required'
        ], [
            'user_id.required' => 'please fill user_id',
            'food.required' => 'please fill food_id',
            'type.required' => 'please fill type',
            'quantity.required' => 'please fill type',
        ]);
        if ($validator->fails()) {
            return response()->json(["error" => $validator->errors(), "success" => false], 400);
        }
        try {
            $cart = Cart::where("user_id", $req->user_id)->first();
            if ($cart) {
                $foodItemsArray = $cart->foodItems;
                foreach ($foodItemsArray as &$value) {
                    if ($req->food_id == $value['food_id']) {
                        if ($req->type == 'increase') {
                            $value['quantity'] = ($value['quantity'] + $req->quantity);
                        }
                        if ($req->type == 'decrease') {
                            $value['quantity'] = ($value['quantity'] - $req->quantity);
                        }
                    }
                }
                Cart::where("user_id", $req->user_id)->update(["foodItems" => $foodItemsArray]);
                return response()->json(["msg" => "updated successfully", "success" => true], 200);
            } else {
                return response()->json(["msg" => "updated successfully", "success" => true], 200);
            }

        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
        }
    }

    function removeItemFromCart(Request $req)
    {
        if (!$req->user_id || !$req->food_id) {
            return response()->json(["error" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            $cart = Cart::where("user_id", $req->user_id)->first();
            $foodItemsArray = $cart->foodItems;
            $food_id = $req->food_id;
            $filteredFoods = array_filter($foodItemsArray, function ($food) use ($food_id) {
                return $food['food_id'] !== $food_id;
            });

            $filteredFoods = array_values($filteredFoods);
            if (count($filteredFoods) > 0) {
                Cart::where("user_id", $req->user_id)->update(["foodItems" => $filteredFoods]);
            }else{
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