<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCouponRequest;
use App\Models\Coupon;
use Illuminate\Http\Request;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $query = Coupon::query();
        // Add search, filter, sort, pagination logic here
        $coupons = $query->paginate(20);
        return response()->json($coupons);
    }

    public function store(StoreCouponRequest $request)
    {
        $coupon = Coupon::create($request->validated());
        return response()->json(['message' => 'Coupon created successfully.', 'coupon' => $coupon], 201);
    }

    public function show($id)
    {
        $coupon = Coupon::with('usages')->findOrFail($id);
        return response()->json($coupon);
    }

    public function update(StoreCouponRequest $request, $id)
    {
        $coupon = Coupon::findOrFail($id);
        $coupon->update($request->validated());
        return response()->json(['message' => 'Coupon updated successfully.', 'coupon' => $coupon]);
    }

    public function destroy($id)
    {
        $coupon = Coupon::findOrFail($id);
        if ($coupon->usages()->count() > 0) {
            $coupon->status = 'inactive';
            $coupon->save();
            return response()->json(['message' => 'Coupon marked as inactive.'], 200);
        }
        $coupon->delete();
        return response()->json(['message' => 'Coupon deleted.'], 200);
    }
}
