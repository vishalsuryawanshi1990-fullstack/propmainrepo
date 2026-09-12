<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Mail\PaymentReceiptMail;
use App\Models\Coupon;
use App\Models\CouponRedemption;
use App\Models\Payment;
use App\Models\User;
use App\Services\Monetization\ScratchCardService;
use App\Services\Monetization\WalletService;
use App\Services\Notifications\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Razorpay\Api\Errors\SignatureVerificationError;
use Razorpay\Api\Utility;

/**
 * 05-security-compliance.md: "Razorpay webhook signature verification on
 * every webhook call — never trust an unsigned 'payment succeeded' hit."
 * This, not the app's checkout success callback, is what actually credits
 * a wallet — idempotent on gateway_payment_id so a retried webhook can't
 * double-credit.
 */
class RazorpayWebhookController extends Controller
{
    public function handle(Request $request, WalletService $wallets, ScratchCardService $scratchCards, NotificationService $notifications): JsonResponse
    {
        $signature = $request->header('X-Razorpay-Signature', '');

        try {
            (new Utility)->verifyWebhookSignature($request->getContent(), $signature, config('services.razorpay.webhook_secret'));
        } catch (SignatureVerificationError $e) {
            Log::warning('Razorpay webhook signature verification failed', ['error' => $e->getMessage()]);

            return response()->apiError('Invalid signature.', [], 400);
        }

        $event = $request->input('event');

        if ($event !== 'payment.captured') {
            return response()->apiSuccess(null, 'Ignored.');
        }

        $paymentEntity = $request->input('payload.payment.entity', []);
        $orderId = $paymentEntity['order_id'] ?? null;
        $gatewayPaymentId = $paymentEntity['id'] ?? null;

        $payment = Payment::where('gateway_order_id', $orderId)->first();

        if (! $payment || $payment->status === 'paid') {
            return response()->apiSuccess(null, 'Ignored.');
        }

        DB::transaction(function () use ($payment, $gatewayPaymentId, $wallets, $scratchCards, $notifications) {
            $payment->forceFill([
                'status' => 'paid',
                'webhook_verified' => true,
                'gateway_payment_id' => $gatewayPaymentId,
            ])->save();

            $this->fulfill($payment, $wallets, $scratchCards, $notifications);
        });

        return response()->apiSuccess(null, 'Processed.');
    }

    private function fulfill(Payment $payment, WalletService $wallets, ScratchCardService $scratchCards, NotificationService $notifications): void
    {
        $user = User::findOrFail($payment->user_id);

        if ($user->email) {
            Mail::to($user->email)->queue(new PaymentReceiptMail($payment));
        }

        if ($payment->purpose !== 'coupon_purchase') {
            return;
        }

        $coupon = Coupon::find($payment->metadata['coupon_id'] ?? null);

        if (! $coupon) {
            return;
        }

        if ($coupon->type === 'fixed_credits') {
            $wallets->credit($user->wallet, (int) $coupon->value, 'coupon', $payment->id);
        }

        CouponRedemption::create([
            'coupon_id' => $coupon->id,
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'redeemed_at' => now(),
        ]);

        $coupon->increment('redemptions_count');

        $scratchCards->spawn($user, 'coupon');
        $notifications->notify($user, 'scratch_card.ready', 'Scratch card ready!', 'Scratch your card to reveal your reward.');
    }
}
