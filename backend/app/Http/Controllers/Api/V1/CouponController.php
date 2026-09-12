<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseCouponRequest;
use App\Http\Requests\RedeemCouponRequest;
use App\Http\Resources\CouponResource;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Services\Monetization\WalletService;
use App\Services\Payments\RazorpayService;
use Illuminate\Http\JsonResponse;

class CouponController extends Controller
{
    public function index(): JsonResponse
    {
        $coupons = Coupon::where('is_active', true)
            ->where(fn ($q) => $q->whereNull('valid_until')->orWhere('valid_until', '>=', now()))
            ->get();

        return response()->apiSuccess(CouponResource::collection($coupons));
    }

    /**
     * Creates a Razorpay order — the wallet is credited only by the
     * webhook once Razorpay confirms payment, never here.
     */
    public function purchase(PurchaseCouponRequest $request, RazorpayService $razorpay): JsonResponse
    {
        $coupon = Coupon::findOrFail($request->integer('coupon_id'));

        $this->assertPurchasable($coupon);

        $payment = $razorpay->createOrder(
            $request->user(),
            (int) $coupon->price,
            'coupon_purchase',
            ['coupon_id' => $coupon->id],
        );

        return response()->apiSuccess([
            'order_id' => $payment->gateway_order_id,
            'amount' => (int) $payment->amount,
            'currency' => 'INR',
            'key_id' => config('services.razorpay.key'),
        ], 'Order created.', [], 201);
    }

    /**
     * Free promo-code redemption — only fixed_credits coupons make sense
     * here (a percentage_discount coupon applies at purchase time, which
     * is a post-MVP feature per doc11).
     */
    public function redeem(RedeemCouponRequest $request, WalletService $wallets): JsonResponse
    {
        $coupon = Coupon::where('code', $request->string('code')->toString())->firstOrFail();

        $this->assertPurchasable($coupon);

        abort_unless($coupon->type === 'fixed_credits', 422, 'This coupon type cannot be redeemed directly.');

        $user = $request->user();

        $wallets->credit($user->wallet, (int) $coupon->value, 'coupon', $coupon->id);

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'redeemed_at' => now(),
        ]);

        $coupon->increment('redemptions_count');

        return response()->apiSuccess(null, 'Coupon redeemed.');
    }

    private function assertPurchasable(Coupon $coupon): void
    {
        abort_unless($coupon->is_active, 422, 'This coupon is no longer active.');
        abort_if($coupon->valid_until && $coupon->valid_until->isPast(), 422, 'This coupon has expired.');
        abort_if($coupon->max_redemptions && $coupon->redemptions_count >= $coupon->max_redemptions, 422, 'This coupon has been fully redeemed.');
    }
}
