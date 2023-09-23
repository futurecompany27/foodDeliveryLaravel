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
            'cartDeliveryDate' => 'required',
            'cartData' => 'required',
        ], [
            'user_id.required' => 'please fill user_id',
            'cartDeliveryDate.required' => 'please fill delivery date',
            'cartData.required' => 'please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $mycart = Cart::where("user_id", $req->user_id)->first();
            if ($mycart) {
                Cart::where("user_id", $req->user_id)->update(['cartData' => $req->cartData]);
            } else {
                $cart = new Cart();
                $cart->user_id = $req->user_id;
                $cart->cartDeliveryDate = $req->cartDeliveryDate;
                $cart->cartData = $req->cartData;
                $cart->save();
            }

            return response()->json(["message" => "Added successfully", "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function getMyCart(Request $req)
    {
        // if (!$req->user_id) {
        //     return response()->json(["error" => "please fill all the required fields", "success" => false], 400);
        // }
        try {
            if ($req->user_id) {
                $data = Cart::where('user_id', $req->user_id)->first();
                if ($data) {
                    $myCart = $data->cartData;
                    foreach ($myCart as &$chefData) {
                        $chef = chef::with('foodItems')->find($chefData['chef_id']);
                        if (in_array($data->cartDeliveryDate['weekdayShort'], $chef->chefAvailibilityWeek)) {
                            $chefData['chefAvailable'] = true;
                        } else {
                            $chefData['chefAvailable'] = false;
                        }
                        $foodItems = $chef['foodItems'];
                        foreach ($chefData['foodItems'] as &$food) {
                            $foodItem = $foodItems->firstWhere('id', $food['food_id']);
                            if ($foodItem) {
                                $food['availableToday'] = in_array($data->cartDeliveryDate['weekdayShort'], $foodItem['foodAvailibiltyOnWeekdays']) ? true : false;
                                $food['price'] = $foodItem['price'];
                                $food['dish_name'] = $foodItem['dish_name'];
                                $food['dishImage'] = $foodItem['dishImage'];
                            }
                        }
                    }
                    return response()->json(["data" => $myCart, "cartDeliveryDate" => $data->cartDeliveryDate, "success" => true], 200);
                }
            }
            if ($req->cartData && $req->cartDeliveryDate) {
                $myCart = $req->cartData;
                $cartDeliveryDate = $req->cartDeliveryDate;
                foreach ($myCart as &$chefData) {
                    $chef = chef::with('foodItems')->find($chefData['chef_id']);
                    if (in_array($cartDeliveryDate['weekdayShort'], $chef->chefAvailibilityWeek)) {
                        $chefData['chefAvailable'] = true;
                    } else {
                        $chefData['chefAvailable'] = false;
                    }
                    $foodItems = $chef['foodItems'];
                    foreach ($chefData['foodItems'] as &$food) {
                        $foodItem = $foodItems->firstWhere('id', $food['food_id']);
                        if ($foodItem) {
                            $food['availableToday'] = $chefData['chefAvailable'] ? (in_array($cartDeliveryDate['weekdayShort'], $foodItem['foodAvailibiltyOnWeekdays']) ? true : false) : false;
                            $food['price'] = $foodItem['price'];
                            $food['dish_name'] = $foodItem['dish_name'];
                            $food['dishImage'] = $foodItem['dishImage'];
                        }
                    }
                }
                return response()->json(["data" => $myCart, "cartDeliveryDate" => $cartDeliveryDate, "success" => true], 200);
            }
            return response()->json(["data" => [], "success" => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
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
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
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
            foreach ($cartData as &$value) {
                if ($value['chef_id'] == $req->chef_id) {
                    $value['foodItems'] = array_filter($value['foodItems'], function ($food) use ($req) {
                        return $food['food_id'] !== $req->food_id;
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
            return response()->json(['message' => 'Removed successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false]);
        }
    }

    function addBeforeLoginCartData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'user_id' => 'required',
            'cartDeliveryDate' => 'required',
            'cartData' => 'required',
        ], [
            'user_id.required' => 'please fill user_id',
            'cartDeliveryDate.required' => 'please fill delivery date',
            'cartData.required' => 'please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $mycart = Cart::where("user_id", $req->user_id)->first();
            if ($mycart) {
                $oldCartData = $mycart->cartData;
                $newCartData = $req->cartData;

                // Create an associative array to store the merged data
                $mergedData = [];

                // Merge $oldCartData and $newCartData
                foreach (array_merge($oldCartData, $newCartData) as $item) {
                    $chefId = $item['chef_id'];

                    if (!isset($mergedData[$chefId])) {
                        // If chef_id doesn't exist in the mergedData, add it
                        $mergedData[$chefId] = [
                            'chef_id' => $chefId,
                            'chefName' => $item['chefName'],
                            'foodItems' => $item['foodItems'],
                        ];
                    } else {
                        // If chef_id already exists, merge the foodItems
                        foreach ($item['foodItems'] as $newFoodItem) {
                            $found = false;
                            foreach ($mergedData[$chefId]['foodItems'] as &$mergedFoodItem) {
                                if ($newFoodItem['food_id'] == $mergedFoodItem['food_id']) {
                                    // Update the price from $newCartData
                                    $mergedFoodItem['price'] = $newFoodItem['price'];
                                    $mergedFoodItem['quantity'] = $newFoodItem['quantity'];
                                    $mergedFoodItem['dishImage'] = $newFoodItem['dishImage'];
                                    $found = true;
                                    break;
                                }
                            }
                            if (!$found) {
                                // Add the new food item to the mergedData
                                $mergedData[$chefId]['foodItems'][] = $newFoodItem;
                            }
                        }
                    }
                }
                // Convert the associative array back to indexed array
                $finalMergedData = array_values($mergedData);
                Cart::where("user_id", $req->user_id)->update(['cartData' => $finalMergedData, 'cartDeliveryDate' => $req->cartDeliveryDate], 200);
                return response()->json(['message' => 'Successfully merged data', 'success' => true], 200);
            } else {
                $cart = new Cart();
                $cart->user_id = $req->user_id;
                $cart->cartDeliveryDate = $req->cartDeliveryDate;
                $cart->cartData = $req->cartData;
                $cart->save();
            }
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }

    function updateCartDeliveryDate(Request $req)
    {
        if (!$req->user_id || !$req->cartDeliveryDate) {
            return response()->json(["error" => "please fill all the required fields", "success" => false], 400);
        }
        try {
            Cart::where("user_id", $req->user_id)->update(['cartDeliveryDate' => $req->cartDeliveryDate]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Throwable $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong. Please try again !', 'success' => false], 500);
        }
    }
}