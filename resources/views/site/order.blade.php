@extends('layouts.site')

@section('title', 'Order '.$order->order_number)
@section('header-class', 'bg-ink')

@push('head')<meta name="robots" content="noindex">@endpush

@section('content')
@php
    $steps = $order->type === 'delivery'
        ? ['pending' => 'Order received', 'confirmed' => 'Confirmed', 'preparing' => 'In the kitchen', 'out_for_delivery' => 'On the way', 'completed' => 'Delivered']
        : ['pending' => 'Order received', 'confirmed' => 'Confirmed', 'preparing' => 'In the kitchen', 'ready' => 'Ready to collect', 'completed' => 'Collected'];
    $keys = array_keys($steps);
    $current = array_search($order->status, $keys);
    $cancelled = $order->status === 'cancelled';
    $unpaidOnline = $order->payment_method === 'razorpay' && $order->payment_status !== 'paid' && ! $cancelled;
@endphp

<section class="wrap max-w-4xl pb-24 pt-36">
    <div class="text-center">
        @if (session('placed'))
            <div class="mx-auto grid size-20 place-items-center rounded-full border border-gold text-3xl text-gold rise">✓</div>
            <p class="eyebrow mt-8">Shukriya, {{ Str::before($order->customer_name, ' ') }}</p>
            <h1 class="display mt-4 text-4xl text-cream md:text-5xl">Your order is <span class="gold-text">placed</span></h1>
        @else
            <p class="eyebrow">Order status</p>
            <h1 class="display mt-4 text-4xl text-cream md:text-5xl">{{ $cancelled ? 'Order cancelled' : $order->status_label }}</h1>
        @endif
        <div class="ornament mt-6 justify-center"><span></span></div>
        <p class="mt-6 text-sand">Order number</p>
        <p class="font-display text-2xl tracking-[0.2em] text-gold-light">{{ $order->order_number }}</p>
        <p class="mt-2 text-sm text-sand">Placed {{ $order->created_at->format('d M Y, h:i A') }} · Keep this number to track your order.</p>
    </div>

    @if ($unpaidOnline)
        <div class="mt-10 flex flex-col items-center justify-between gap-4 border border-ruby/60 bg-ruby/10 p-6 text-center md:flex-row md:text-left">
            <p class="text-cream">Payment is still pending for this order. We start cooking once it is paid.</p>
            <a href="{{ route('order.pay', $order->token) }}" class="btn btn-gold btn-sm flex-none">Pay {{ money($order->total) }} now</a>
        </div>
    @endif

    {{-- Progress --}}
    @unless ($cancelled)
        <ol class="mt-12 grid grid-cols-5 gap-2">
            @foreach ($steps as $key => $label)
                @php $done = $current !== false && $loop->index <= $current; @endphp
                <li class="text-center">
                    <div class="relative flex items-center justify-center">
                        @unless ($loop->first)<span class="absolute right-1/2 top-1/2 h-px w-full -translate-y-1/2 {{ $done ? 'bg-gold' : 'bg-white/10' }}"></span>@endunless
                        <span class="relative z-10 grid size-9 place-items-center rounded-full border text-sm {{ $done ? 'border-gold bg-gold text-ink' : 'border-white/15 bg-ink text-sand' }}">{{ $done ? '✓' : $loop->iteration }}</span>
                    </div>
                    <p class="mt-3 text-[0.62rem] uppercase leading-snug tracking-[0.14em] md:text-xs {{ $done ? 'text-gold-light' : 'text-sand' }}">{{ $label }}</p>
                </li>
            @endforeach
        </ol>
    @endunless

    <div class="mt-12 grid gap-6 md:grid-cols-5">
        <div class="card p-6 md:col-span-3 md:p-8">
            <p class="eyebrow">Your dishes</p>
            <ul class="mt-4 divide-y divide-white/5">
                @foreach ($order->items as $item)
                    <li class="flex items-baseline justify-between gap-4 py-3.5">
                        <span class="text-cream">{{ $item->name }} <span class="text-sand">× {{ $item->quantity }}</span></span>
                        <span class="text-gold-light">{{ money($item->total) }}</span>
                    </li>
                @endforeach
            </ul>
            <dl class="mt-3 space-y-2.5 border-t border-gold/15 pt-5 text-cream/90">
                <div class="flex justify-between"><dt class="text-sand">Subtotal</dt><dd>{{ money($order->subtotal) }}</dd></div>
                @if ((float) $order->discount > 0)<div class="flex justify-between text-leaf"><dt>Coupon {{ $order->coupon_code }}</dt><dd>− {{ money($order->discount) }}</dd></div>@endif
                @if ($order->type === 'delivery')<div class="flex justify-between"><dt class="text-sand">Delivery</dt><dd>{{ (float) $order->delivery_charge > 0 ? money($order->delivery_charge) : 'Free' }}</dd></div>@endif
                @if ((float) $order->tax > 0)<div class="flex justify-between"><dt class="text-sand">Taxes</dt><dd>{{ money($order->tax) }}</dd></div>@endif
                <div class="flex items-baseline justify-between border-t border-gold/15 pt-4"><dt class="text-sm uppercase tracking-[0.22em] text-sand">Total</dt><dd class="font-display text-3xl text-gold-light">{{ money($order->total) }}</dd></div>
            </dl>
        </div>

        <div class="card space-y-6 p-6 md:col-span-2 md:p-8">
            <div>
                <p class="eyebrow">{{ $order->type_label }}</p>
                <p class="mt-3 text-cream">{{ $order->customer_name }}</p>
                <p class="text-sand">{{ $order->phone }}</p>
                @if ($order->address)<p class="mt-2 text-sand">{{ $order->address }}@if ($order->landmark), near {{ $order->landmark }}@endif</p>@endif
            </div>
            <div>
                <p class="eyebrow">Payment</p>
                <p class="mt-3 text-cream">{{ $order->payment_method === 'razorpay' ? 'Online payment' : ($order->type === 'delivery' ? 'Cash on delivery' : 'Pay at counter') }}</p>
                <p class="{{ $order->payment_status === 'paid' ? 'text-leaf' : 'text-sand' }}">{{ $order->payment_status === 'paid' ? 'Paid ✓' : ucfirst($order->payment_status) }}</p>
            </div>
            @if ($order->notes)<div><p class="eyebrow">Instructions</p><p class="mt-3 text-sand">{{ $order->notes }}</p></div>@endif
            @if (setting('phone'))<a href="tel:{{ setting('phone') }}" class="btn btn-line btn-sm w-full">Call the restaurant</a>@endif
        </div>
    </div>

    <div class="mt-12 text-center"><a href="{{ route('menu') }}" class="link-gold">Back to the menu</a></div>
</section>
@endsection
