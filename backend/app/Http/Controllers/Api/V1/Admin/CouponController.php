<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCouponRequest;
use App\Http\Requests\Admin\UpdateCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->apiSuccess(CouponResource::collection(Coupon::latest()->paginate(20)));
    }

    public function store(StoreCouponRequest $request): JsonResponse
    {
        $coupon = Coupon::create($request->validated());
        AuditLogger::log('coupon.create', $coupon, null, $coupon->toArray());

        return response()->apiSuccess(new CouponResource($coupon), 'Coupon created.', [], 201);
    }

    public function update(UpdateCouponRequest $request, Coupon $coupon): JsonResponse
    {
        $before = $coupon->toArray();
        $coupon->update($request->validated());
        AuditLogger::log('coupon.update', $coupon, $before, $coupon->fresh()->toArray());

        return response()->apiSuccess(new CouponResource($coupon), 'Coupon updated.');
    }

    public function destroy(Coupon $coupon): JsonResponse
    {
        $before = $coupon->toArray();
        $coupon->delete();
        AuditLogger::log('coupon.delete', $coupon, $before, null);

        return response()->apiSuccess(null, 'Coupon deleted.');
    }
}
