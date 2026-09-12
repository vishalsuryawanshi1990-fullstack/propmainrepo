<x-mail::message>
# Payment Receipt

Thanks for your purchase.

| | |
|---|---|
| Amount | ₹{{ number_format((float) $payment->amount, 2) }} |
| Purpose | {{ str($payment->purpose)->replace('_', ' ')->title() }} |
| Payment ID | {{ $payment->gateway_payment_id }} |
| Date | {{ $payment->updated_at->format('d M Y, h:i A') }} |

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
