<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Exception;

class CouponController extends Controller
{
    public function getAllCoupons()
    {
        try {

            $coupons = Coupon::orderBy('id', 'DESC')->get();

            return response()->json([
                'status' => true,
                'message' => 'Coupon list fetched successfully',
                'data' => $coupons
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function addCoupon(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'code' => [
                    'required',
                    'string',
                    'min:4',
                    'max:10',
                    'regex:/^[A-Za-z0-9]+$/',
                    'unique:coupons,code'
                ],
                'description' => 'nullable|string|max:1000',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_value' => 'required|numeric|min:1',
                'max_discount' => 'nullable|numeric|min:1',
                'min_order_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'per_user_limit' => 'nullable|integer|min:1',
                'first_time_only' => 'required|in:0,1',
                'one_time_per_use' => 'required|in:0,1',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:active,inactive'
            ], [
                'code.unique' => 'Coupon code already exists.',
                'code.regex' => 'Coupon code can contain only letters and numbers.',
                'discount_value.min' => 'Discount value must be greater than 0.',
                'end_date.after' => 'End date must be greater than start date.'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            if (
                $request->discount_type === 'percentage' &&
                $request->discount_value > 100
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Percentage discount cannot exceed 100%.'
                ]);
            }

            if (
                $request->discount_type === 'percentage' &&
                empty($request->max_discount)
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maximum discount is required for percentage coupons.'
                ]);
            }

            if (
                !empty($request->usage_limit) &&
                !empty($request->per_user_limit) &&
                $request->per_user_limit > $request->usage_limit
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Per user limit cannot exceed total usage limit.'
                ]);
            }

            Coupon::create([
                'code' => strtoupper(trim($request->code)),
                'description' => $request->description,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'max_discount' => $request->max_discount,
                'min_order_amount' => $request->min_order_amount,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit,
                'first_time_only' => (bool)$request->first_time_only,
                'one_time_per_use' => (bool)$request->one_time_per_use,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Coupon added successfully'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateCoupon(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:coupons,id',
                'code' => [
                    'required',
                    'string',
                    'min:4',
                    'max:10',
                    'regex:/^[A-Za-z0-9]+$/',
                    'unique:coupons,code,' . $request->id
                ],
                'description' => 'nullable|string|max:1000',
                'discount_type' => 'required|in:fixed,percentage',
                'discount_value' => 'required|numeric|min:1',
                'max_discount' => 'nullable|numeric|min:1',
                'min_order_amount' => 'nullable|numeric|min:0',
                'usage_limit' => 'nullable|integer|min:1',
                'per_user_limit' => 'nullable|integer|min:1',
                'first_time_only' => 'required|in:0,1',
                'one_time_per_use' => 'required|in:0,1',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after:start_date',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            if (
                $request->discount_type === 'percentage' &&
                $request->discount_value > 100
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Percentage discount cannot exceed 100%.'
                ]);
            }

            if (
                $request->discount_type === 'percentage' &&
                empty($request->max_discount)
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Maximum discount is required for percentage coupons.'
                ]);
            }

            if (
                !empty($request->usage_limit) &&
                !empty($request->per_user_limit) &&
                $request->per_user_limit > $request->usage_limit
            ) {
                return response()->json([
                    'status' => false,
                    'message' => 'Per user limit cannot exceed total usage limit.'
                ]);
            }

            $coupon = Coupon::find($request->id);

            $coupon->update([
                'code' => strtoupper(trim($request->code)),
                'description' => $request->description,
                'discount_type' => $request->discount_type,
                'discount_value' => $request->discount_value,
                'max_discount' => $request->max_discount,
                'min_order_amount' => $request->min_order_amount,
                'usage_limit' => $request->usage_limit,
                'per_user_limit' => $request->per_user_limit,
                'first_time_only' => (bool)$request->first_time_only,
                'one_time_per_use' => (bool)$request->one_time_per_use,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status' => $request->status
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Coupon updated successfully'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function deleteCoupon(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:coupons,id'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            Coupon::find($request->id)->delete();

            return response()->json([
                'status' => true,
                'message' => 'Coupon deleted successfully'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function updateCouponStatus(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:coupons,id',
                'status' => 'required|in:active,inactive'
            ]);

            if ($validator->fails()) {

                return response()->json([
                    'status' => false,
                    'message' => $validator->errors()->first()
                ]);
            }

            $coupon = Coupon::find($request->id);

            $coupon->status = $request->status;
            $coupon->save();

            return response()->json([
                'status' => true,
                'message' => 'Coupon status updated successfully'
            ]);

        } catch (Exception $e) {

            return response()->json([
                'status' => false,
                'message' => 'Something went wrong.',
                'error' => $e->getMessage()
            ]);
        }
    }
}
