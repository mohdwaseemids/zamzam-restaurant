@extends('layouts.site')

@section('title', 'Payment — Order '.$order->order_number)
@section('header-class', 'bg-ink')

@section('content')
<section class="wrap grid min-h-svh place-items-center pb-24 pt-36">
    <div class="card w-full max-w-lg p-8 text-center md:p-12" x-data="payment">
        <p class="eyebrow">Secure payment</p>
        <h1 class="display mt-4 text-3xl text-cream">Order {{ $order->order_number }}</h1>
        <div class="ornament mt-6 justify-center"><span></span></div>

        <p class="mt-8 text-sm uppercase tracking-[0.24em] text-sand">Amount to pay</p>
        <p class="mt-2 font-display text-5xl text-gold-light">{{ money($order->total) }}</p>

        <button type="button" class="btn btn-gold mt-10 w-full" @click="open()" :disabled="busy">
            <span x-show="!busy">Pay now</span><span x-cloak x-show="busy">Opening payment…</span>
        </button>
        <p x-cloak x-show="message" x-text="message" class="field-error mt-4"></p>
        <p class="mt-5 text-sm text-sand">UPI · Cards · Net banking · Wallets — powered by Razorpay. We never see your card details.</p>
        <a href="{{ route('order.show', $order->token) }}" class="mt-6 inline-block text-sm text-sand hover:text-gold">Pay later / view order</a>

        <form x-ref="form" method="POST" action="{{ route('order.verify', $order->token) }}" class="hidden">
            @csrf
            <input type="hidden" name="razorpay_order_id" x-ref="orderId">
            <input type="hidden" name="razorpay_payment_id" x-ref="paymentId">
            <input type="hidden" name="razorpay_signature" x-ref="signature">
        </form>
    </div>
</section>
@endsection

@push('scripts')
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('payment', () => ({
            busy: false,
            message: '',
            open() {
                if (typeof Razorpay === 'undefined') {
                    this.message = 'Payment could not load. Please check your internet connection and try again.';
                    return;
                }

                this.busy = true;
                this.message = '';

                const checkout = new Razorpay({
                    key: @json($razorpayKey),
                    order_id: @json($razorpayOrderId),
                    amount: {{ (int) round(((float) $order->total) * 100) }},
                    currency: 'INR',
                    name: @json(setting('site_name', 'Zam Zam Family Restaurant')),
                    description: 'Order ' + @json($order->order_number),
                    prefill: { name: @json($order->customer_name), contact: @json($order->phone), email: @json($order->email ?? '') },
                    theme: { color: '#b8893a' },
                    handler: (response) => {
                        this.$refs.orderId.value = response.razorpay_order_id;
                        this.$refs.paymentId.value = response.razorpay_payment_id;
                        this.$refs.signature.value = response.razorpay_signature;
                        this.$refs.form.submit();
                    },
                    modal: { ondismiss: () => { this.busy = false; } },
                });

                checkout.on('payment.failed', (response) => {
                    this.busy = false;
                    this.message = (response.error && response.error.description) || 'Payment failed. Please try again.';
                });

                checkout.open();
            },
            init() {
                // Open the payment window straight away; the button stays as a fallback.
                window.addEventListener('load', () => this.open(), { once: true });
            },
        }));
    });
</script>
@endpush
