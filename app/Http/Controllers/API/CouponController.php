<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Coupon;
use Illuminate\Http\Response;

class CouponController extends Controller
{
    public function index()
    {
        return response()->json(Coupon::withCount('usages')->paginate(20));
    }

    public function show($id)
    {
        $coupon = Coupon::with('usages')->findOrFail($id);
        return response()->json($coupon);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => 'required|string|unique:coupons,code',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric',
            'max_discount' => 'nullable|numeric',
            'min_order_amount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer',
            'per_user_limit' => 'nullable|integer',
            'first_time_only' => 'boolean',
            'one_time_per_use' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'in:active,inactive',
        ]);

        $coupon = Coupon::create($data);

        return response()->json($coupon, Response::HTTP_CREATED);
    }

    public function update(Request $request, $id)
    {
        $coupon = Coupon::findOrFail($id);

        $data = $request->validate([
            'code' => "required|string|unique:coupons,code,{$id}",
            'description' => 'nullable|string',
            'discount_type' => 'required|in:fixed,percentage',
            'discount_value' => 'required|numeric',
            'max_discount' => 'nullable|numeric',
            'min_order_amount' => 'nullable|numeric',
            'usage_limit' => 'nullable|integer',
            'per_user_limit' => 'nullable|integer',
            'first_time_only' => 'boolean',
            'one_time_per_use' => 'boolean',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'in:active,inactive',
        ]);

        $coupon->update($data);

        return response()->json($coupon);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->delete();
        return response()->json(['message' => 'Deleted'], Response::HTTP_NO_CONTENT);
    }

    /**
     * Single route handler that dispatches CRUD based on HTTP method.
     */
    public function single(Request $request, $id = null)
    {
        switch ($request->method()) {
            case 'GET':
                return $id ? $this->show($id) : $this->index();
            case 'POST':
                return $this->store($request);
            case 'PUT':
            case 'PATCH':
                if (!$id) return response()->json(['message' => 'Resource id required for update'], 400);
                return $this->update($request, $id);
            case 'DELETE':
                if (!$id) return response()->json(['message' => 'Resource id required for delete'], 400);
                return $this->destroy($id);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }
}
