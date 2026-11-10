<?php

namespace App\Services;

use App\Models\Order;
use Razorpay\Api\Api;

class RazorpayService
{
    public function keyId(): ?string
    {
        return setting('razorpay_key_id') ?: config('services.razorpay.key_id');
    }

    protected function keySecret(): ?string
    {
        return setting('razorpay_key_secret') ?: config('services.razorpay.key_secret');
    }

    protected function webhookSecret(): ?string
    {
        return setting('razorpay_webhook_secret') ?: config('services.razorpay.webhook_secret');
    }

    public function isConfigured(): bool
    {
        return filled($this->keyId()) && filled($this->keySecret());
    }

    /** Create (or reuse) the Razorpay order for one of our orders. Amount is always taken from our database. */
    public function createOrder(Order $order): string
    {
        if (filled($order->razorpay_order_id)) {
            return $order->razorpay_order_id;
        }

        $api = new Api($this->keyId(), $this->keySecret());

        $rzpOrder = $api->order->create([
            'receipt' => $order->order_number,
            'amount' => (int) round(((float) $order->total) * 100), // paise
            'currency' => 'INR',
            'notes' => ['order_number' => $order->order_number],
        ]);

        $order->update(['razorpay_order_id' => $rzpOrder['id']]);

        return $rzpOrder['id'];
    }

    /** Verify the signature Razorpay Checkout returns to the browser. */
    public function verifyPaymentSignature(string $razorpayOrderId, string $paymentId, string $signature): bool
    {
        if (blank($this->keySecret())) {
            return false;
        }

        $expected = hash_hmac('sha256', $razorpayOrderId.'|'.$paymentId, $this->keySecret());

        return hash_equals($expected, $signature);
    }

    /** Verify the X-Razorpay-Signature header of a webhook call. */
    public function verifyWebhookSignature(string $payload, ?string $signature): bool
    {
        if (blank($this->webhookSecret()) || blank($signature)) {
            return false;
        }

        return hash_equals(hash_hmac('sha256', $payload, $this->webhookSecret()), $signature);
    }
}
