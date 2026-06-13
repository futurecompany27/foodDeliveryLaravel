<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\CouponUsage;
use Illuminate\Http\Response;

class CouponUsageController extends Controller
{
    public function index()
    {
        return response()->json(CouponUsage::with(['coupon','user'])->paginate(20));
    }

    public function show($id)
    {
        $usage = CouponUsage::with(['coupon','user'])->findOrFail($id);
        return response()->json($usage);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'coupon_id' => 'required|exists:coupons,id',
            'user_id' => 'required|exists:users,id',
            'order_id' => 'nullable|integer',
            'used_at' => 'nullable|date',
        ]);

        $usage = CouponUsage::create(array_merge($data, ['used_at' => $data['used_at'] ?? now()]));

        return response()->json($usage, Response::HTTP_CREATED);
    }

    /**
     * Single-route handler for coupon usages.
     */
    public function single(Request $request, $id = null)
    {
        switch ($request->method()) {
            case 'GET':
                return $id ? $this->show($id) : $this->index();
            case 'POST':
                return $this->store($request);
            default:
                return response()->json(['message' => 'Method not allowed'], 405);
        }
    }
}
