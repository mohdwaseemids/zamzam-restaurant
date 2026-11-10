@extends('site.account.layout')

@section('title', 'My account — '.setting('site_name', 'Zam Zam Family Restaurant'))

@section('account')
{{-- Live order banner --}}
@if ($activeOrder)
    <a href="{{ route('order.show', $activeOrder->token) }}" class="group mb-8 flex flex-wrap items-center justify-between gap-4 border border-gold/60 bg-gold/10 p-5 md:p-6">
        <div class="flex items-center gap-4">
            <span class="live-dot flex-none"></span>
            <div>
                <p class="text-xs uppercase tracking-[0.22em] text-gold">Order in progress · {{ $activeOrder->order_number }}</p>
                <p class="mt-1 font-serif text-2xl italic text-cream">{{ $activeOrder->status_label }}</p>
            </div>
        </div>
        <span class="link-gold">Track order</span>
    </a>
@endif

<div class="grid grid-cols-3 gap-3 md:gap-6">
    @foreach ([['Orders', number_format($stats['orders'])], ['Total spent', money($stats['spent'])], ['Saved addresses', $stats['addresses']]] as [$label, $value])
        <div class="card p-4 text-center md:p-6">
            <p class="font-display text-xl text-gold-light md:text-3xl">{{ $value }}</p>
            <p class="mt-2 text-[0.6rem] uppercase tracking-[0.2em] text-sand md:text-xs">{{ $label }}</p>
        </div>
    @endforeach
</div>

<div class="mt-12 grid gap-12 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <div class="flex items-end justify-between">
            <h2 class="font-display text-xl tracking-widest text-gold-light">Recent orders</h2>
            @if ($recentOrders->isNotEmpty())<a href="{{ route('account.orders') }}" class="link-gold">All orders</a>@endif
        </div>

        <div class="mt-6 space-y-4">
            @forelse ($recentOrders as $order)
                @include('site.account.partials.order-row', ['order' => $order])
            @empty
                <div class="card p-10 text-center">
                    <div class="ornament justify-center"><span></span></div>
                    <p class="mt-6 font-serif text-2xl italic text-cream">No orders yet</p>
                    <p class="mt-2 text-sand">Your first dawat is one tap away.</p>
                    <a href="{{ route('menu') }}" class="btn btn-gold mt-7">Browse the menu</a>
                </div>
            @endforelse
        </div>
    </div>

    <aside class="space-y-8">
        <div class="card p-6">
            <p class="eyebrow">Your details</p>
            <p class="mt-4 text-lg text-cream">{{ $user->name }}</p>
            <p class="text-sand">{{ $user->phone ?: 'No mobile number saved' }}</p>
            <p class="text-sand">{{ $user->email ?: 'No email saved' }}</p>
            @unless ($user->email)<p class="mt-3 text-sm text-[#e5c07f]">Add an email so you can reset your password if you forget it.</p>@endunless
            <a href="{{ route('account.settings') }}" class="link-gold mt-5">Edit profile</a>
        </div>

        <div class="card p-6">
            <p class="eyebrow">Table bookings</p>
            @forelse ($bookings as $booking)
                <div class="mt-4 flex items-start justify-between gap-3 border-b border-white/5 pb-4 last:border-0 last:pb-0">
                    <div>
                        <p class="text-cream">{{ $booking->date->format('D, d M') }} · {{ \Illuminate\Support\Carbon::parse($booking->time)->format('h:i A') }}</p>
                        <p class="text-sm text-sand">{{ $booking->guests }} {{ Str::plural('guest', $booking->guests) }}@if ($booking->occasion) · {{ $booking->occasion }}@endif</p>
                    </div>
                    <span class="text-[0.62rem] uppercase tracking-[0.2em] {{ $booking->status === 'confirmed' ? 'text-leaf' : 'text-gold' }}">{{ $booking->status === 'pending' ? 'Awaiting call' : ucfirst($booking->status) }}</span>
                </div>
            @empty
                <p class="mt-4 text-sand">No upcoming bookings.</p>
            @endforelse
            <a href="{{ route('reserve') }}" class="link-gold mt-5">Reserve a table</a>
        </div>
    </aside>
</div>
@endsection
