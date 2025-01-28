<?php

namespace App\Http\Controllers\users;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Chef;
use App\Models\FoodItem;
use App\Models\SubOrders;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    function addToCart(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'user_id' => 'required',
            'cartDeliveryDate' => 'required',
            'cartData' => 'required',
        ], [
            // 'user_id.required' => 'Please fill user_id',
            'cartDeliveryDate.required' => 'Please fill delivery date',
            'cartData.required' => 'Please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            Log::info($req);
            $user = auth()->guard('user')->user();
            $mycart = Cart::where("user_id", $user->id)->first();
            if ($mycart) {
                Cart::where("user_id", $user->id)->update(['cartData' => $req->cartData]);
            } else {
                $cart = new Cart();
                $cart->user_id = $user->id;
                $cart->cartDeliveryDate = $req->cartDeliveryDate;
                $cart->cartData = $req->cartData;
                $cart->save();
            }
            Log::info('Chef-id', [$req->cartData]);
            // if ($req->cartData->is_tax_document_completed == '0') {
            //     // Fetch all orders for this chef
            //     $orders = SubOrders::where('chef_id', $req->cartData->chef_id)->get();
            //     $totalAmount = 0;

            //     // Loop through orders and calculate the total amount
            //     foreach ($orders as $order) {
            //         $totalAmount += $order->amount - $order->chef_commission_amount;
            //     }

            //     // If totalAmount exceeds 4000, mark the chef as inactive
            //     if ($totalAmount > 4000) {
            //         $chef->update(['status' => '0']);
            //         return response()->json(['message' => 'The selected chef is currently unavailable. Please choose another chef', 'success' => true], 200);
            //     }
            // }

            return response()->json(["message" => "Added successfully", "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    // function getMyCart(Request $req)
    // {
    //     try {
    //         $user = auth()->guard('user')->user();
    //         if ($user) {
    //             $data = Cart::where('user_id', $user->id)->first();
    //             if ($data) {
    //                 $myCart = $data->cartData;
    //                 foreach ($myCart as &$chefData) {
    //                     $chef = Chef::with('foodItems')->find($chefData['chef_id']);
    //                     $chefData['chefName'] = $chef->kitchen_name;
    //                     $chefData['chefAvailibilityWeek'] = $chef->chefAvailibilityWeek;
    //                     $chefData['postal_code'] = $chef->postal_code;
    //                     $foodItems = $chef['foodItems'];
    //                     foreach ($chefData['foodItems'] as $food) {
    //                         Log::info('getCart 1', [$food]);
    //                         $foodItem = $foodItems->firstWhere('id', $food['food_id']);
    //                         if ($foodItem) {
    //                             Log::info('getCart 2', [$foodItem]);
    //                             // $food['availableToday'] = in_array($data->cartDeliveryDate['weekdayShort'], $foodItem['foodAvailibiltyOnWeekdays']) ? true : false;
    //                             $food['price'] = $foodItem['price'];
    //                             $food['dish_name'] = $foodItem['dish_name'];
    //                             $food['dishImage'] = $foodItem['dishImage'];
    //                             $food['foodAvailibiltyOnWeekdays'] = $foodItem['foodAvailibiltyOnWeekdays'];
    //                         }
    //                     }
    //                 }
    //                 return response()->json(["data" => $myCart, "cartDeliveryDate" => $data->cartDeliveryDate, "success" => true], 200);
    //             }
    //         }
    //         return response()->json(["data" => [], "success" => true], 200);
    //     } catch (\Exception $th) {
    //         Log::info($th->getMessage());
    //         DB::rollback();
    //         return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
    //     }
    // }



    function getMyCart(Request $req)
    {
        try {
            $user = auth()->guard('user')->user();
            if ($user) {
                $data = Cart::where('user_id', $user->id)->first();
                if ($data) {
                    $myCart = $data->cartData;
                    foreach ($myCart as &$chefData) {
                        $chef = Chef::with('foodItems')->find($chefData['chef_id']);
                        if ($chef) {
                            $chefData['chefName'] = $chef->kitchen_name;
                            $chefData['chefAvailibilityWeek'] = $chef->chefAvailibilityWeek;
                            $chefData['postal_code'] = $chef->postal_code;
                            $foodItems = $chef['foodItems'];

                            // Check if foodItems is an array
                            if (is_array($chefData['foodItems'])) {
                                foreach ($chefData['foodItems'] as &$food) {

                                    // Check if 'food_id' exists and foodItems is iterable
                                    if (is_array($food) && isset($food['food_id'])) {
                                        $foodItem = $foodItems->firstWhere('id', $food['food_id']);
                                        if ($foodItem) {
                                            // $food['availableToday'] = in_array($data->cartDeliveryDate['weekdayShort'], $foodItem['foodAvailibiltyOnWeekdays']) ? true : false;
                                            $food['price'] = $foodItem['price'];
                                            $food['dish_name'] = $foodItem['dish_name'];
                                            $food['dishImage'] = $foodItem['dishImage'];
                                            $food['foodAvailibiltyOnWeekdays'] = $foodItem['foodAvailibiltyOnWeekdays'];
                                        }
                                    } else {
                                        Log::info('Invalid food item or missing food_id', [$food]);
                                    }
                                }
                            } else {
                                Log::info('FoodItems is not an array', [$chefData['foodItems']]);
                            }
                        }
                    }
                    return response()->json(["data" => $myCart, "cartDeliveryDate" => $data->cartDeliveryDate, "success" => true], 200);
                }
            }
            return response()->json(["data" => [], "success" => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }



    function changeQuantity(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'user_id' => 'required',
            'food_id' => 'required',
            'chef_id' => 'required',
            'type' => 'required',
            'quantity' => 'required'
        ], [
            // 'user_id.required' => 'Please fill user_id',
            'food_id.required' => 'Please fill food_id',
            'chef_id.required' => 'Please fill chef_id',
            'type.required' => 'Please fill type',
            'quantity.required' => 'Please fill type',
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            $cartData = Cart::where("user_id", $user->id)->first()->cartData;
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
                Cart::where("user_id", $user->id)->update(["cartData" => $cartData]);
                return response()->json(["message" => "updated successfully", "success" => true], 200);
            } else {
                return response()->json(["message" => "updated successfully", "success" => true], 200);
            }
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    function removeItemFromCart(Request $req)
    {
        if (!$req->food_id || !$req->chef_id) {
            return response()->json(["message" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            $cartData = Cart::where("user_id", $user->id)->first()->cartData;
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
                Cart::where("user_id", $user->id)->update(["cartData" => $cartData]);
            } else {
                Cart::where("user_id", $user->id)->delete();
            }
            return response()->json(['message' => 'Removed successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false]);
        }
    }

    function addBeforeLoginCartData(Request $req)
    {
        $validator = Validator::make($req->all(), [
            // 'user_id' => 'required',
            'cartDeliveryDate' => 'required',
            'cartData' => 'required',
        ], [
            // 'user_id.required' => 'Please fill user_id',
            'cartDeliveryDate.required' => 'Please fill delivery date',
            'cartData.required' => 'Please fill food data'
        ]);
        if ($validator->fails()) {
            return response()->json(["message" => $validator->errors()->first(), "success" => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            $mycart = Cart::where("user_id", $user->id)->first();
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
                Cart::where("user_id", $user->id)->update(['cartData' => $finalMergedData, 'cartDeliveryDate' => $req->cartDeliveryDate]);
            } else {
                $cart = new Cart();
                $cart->user_id = $user->id;
                $cart->cartDeliveryDate = $req->cartDeliveryDate;
                $cart->cartData = $req->cartData;
                $cart->save();
            }
            return response()->json(['message' => 'Successfully merged data', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }

    function updateCartDeliveryDate(Request $req)
    {
        if (!$req->cartDeliveryDate) {
            return response()->json(["error" => "Please fill all the required fields", "success" => false], 400);
        }
        try {
            $user = auth()->guard('user')->user();
            Cart::where("user_id", $user->id)->update(['cartDeliveryDate' => $req->cartDeliveryDate]);
            return response()->json(['message' => 'Updated successfully', 'success' => true], 200);
        } catch (\Exception $th) {
            Log::info($th->getMessage());
            DB::rollback();
            return response()->json(['message' => 'Oops! Something went wrong.', 'success' => false], 500);
        }
    }
}
