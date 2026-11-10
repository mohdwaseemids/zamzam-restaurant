{{-- Expects $order (Order with items or items_count). --}}
@php
    $tone = match (true) {
        $order->status === 'cancelled' => 'border-ruby/50 text-[#e58b7f]',
        $order->status === 'completed' => 'border-leaf/50 text-leaf',
        default => 'border-gold/60 text-gold-light',
    };
    $names = $order->relationLoaded('items') ? $order->items->map(fn ($i) => $i->name.' × '.$i->quantity)->implode(', ') : null;
@endphp
<article class="card p-5 md:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="font-display text-lg tracking-[0.14em] text-cream">{{ $order->order_number }}</p>
            <p class="mt-1 text-sm text-sand">{{ $order->created_at->format('d M Y, h:i A') }} · {{ $order->type_label }}</p>
        </div>
        <span class="border px-3 py-1 text-[0.62rem] font-medium uppercase tracking-[0.2em] {{ $tone }}">{{ $order->status_label }}</span>
    </div>

    @if ($names)<p class="mt-4 text-cream/85">{{ $names }}</p>@endif

    <div class="mt-5 flex flex-wrap items-center justify-between gap-4 border-t border-white/5 pt-4">
        <p>
            <span class="font-display text-xl text-gold-light">{{ money($order->total) }}</span>
            <span class="ml-2 text-sm text-sand">{{ $names ? '' : ($order->items_count ?? 0).' '.Str::plural('item', $order->items_count ?? 0).' · ' }}{{ $order->payment_status === 'paid' ? 'Paid' : ($order->payment_method === 'razorpay' ? 'Payment pending' : 'Cash') }}</span>
        </p>
        <div class="flex flex-wrap gap-2">
            @if ($order->payment_method === 'razorpay' && $order->payment_status !== 'paid' && $order->status !== 'cancelled')
                <a href="{{ route('order.pay', $order->token) }}" class="btn btn-gold btn-sm">Pay now</a>
            @endif
            <a href="{{ route('order.show', $order->token) }}" class="btn btn-line btn-sm">{{ in_array($order->status, ['completed', 'cancelled']) ? 'View' : 'Track' }}</a>
            @if (setting('ordering_enabled', true))
                <form method="POST" action="{{ route('account.reorder', $order) }}">
                    @csrf
                    <button class="btn btn-line btn-sm">Reorder</button>
                </form>
            @endif
        </div>
    </div>
</article>
