<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Bill {{ $order->order_number }}</title>
    <style>
        /* Sized for an 80 mm thermal printer; also prints fine on A4. */
        * { box-sizing: border-box; margin: 0; }
        body { font-family: 'Courier New', ui-monospace, monospace; font-size: 13px; color: #000; background: #eee; padding: 16px; }
        .bill { width: 76mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 5mm 4mm; }
        h1 { font-size: 18px; text-align: center; letter-spacing: 2px; }
        .center { text-align: center; }
        .muted { font-size: 11px; }
        hr { border: 0; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td, th { padding: 2px 0; vertical-align: top; text-align: left; }
        .r { text-align: right; white-space: nowrap; }
        .total td { font-size: 16px; font-weight: bold; padding-top: 4px; }
        .actions { width: 76mm; max-width: 100%; margin: 12px auto 0; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 10px; font: inherit; font-weight: bold; cursor: pointer; border: 1px solid #000; background: #fff; }
        .actions button.primary { background: #000; color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            .bill { width: auto; padding: 0; }
            @page { margin: 4mm; }
        }
    </style>
</head>
<body>
    <div class="bill">
        <h1>{{ strtoupper(Str::before(setting('site_name', 'Zam Zam Family Restaurant'), ' Family')) }}</h1>
        <p class="center">Family Restaurant</p>
        <p class="center muted">{{ setting('address') }}</p>
        @if (setting('phone'))<p class="center muted">Ph: {{ setting('phone') }}</p>@endif
        <hr>
        <table>
            <tr><td>Bill no.</td><td class="r">{{ $order->order_number }}</td></tr>
            <tr><td>Date</td><td class="r">{{ $order->created_at->format('d-m-Y h:i A') }}</td></tr>
            <tr><td>Type</td><td class="r">{{ $order->type_label }}</td></tr>
            <tr><td>Customer</td><td class="r">{{ $order->customer_name }}</td></tr>
            @if ($order->phone)<tr><td>Phone</td><td class="r">{{ $order->phone }}</td></tr>@endif
        </table>
        @if ($order->address)<p class="muted" style="margin-top:4px">Address: {{ $order->address }}@if ($order->landmark), near {{ $order->landmark }}@endif</p>@endif
        <hr>
        <table>
            <tr><th>Item</th><th class="r">Qty</th><th class="r">Amount</th></tr>
            @foreach ($order->items as $item)
                <tr><td>{{ $item->name }}<br><span class="muted">@ {{ number_format((float) $item->price, 2) }}</span></td><td class="r">{{ $item->quantity }}</td><td class="r">{{ number_format((float) $item->total, 2) }}</td></tr>
            @endforeach
        </table>
        <hr>
        <table>
            <tr><td>Subtotal</td><td class="r">{{ number_format((float) $order->subtotal, 2) }}</td></tr>
            @if ((float) $order->discount > 0)<tr><td>Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="r">-{{ number_format((float) $order->discount, 2) }}</td></tr>@endif
            @if ((float) $order->delivery_charge > 0)<tr><td>Delivery</td><td class="r">{{ number_format((float) $order->delivery_charge, 2) }}</td></tr>@endif
            @if ((float) $order->tax > 0)<tr><td>Tax</td><td class="r">{{ number_format((float) $order->tax, 2) }}</td></tr>@endif
            <tr class="total"><td>TOTAL Rs.</td><td class="r">{{ number_format((float) $order->total, 2) }}</td></tr>
        </table>
        <hr>
        <p>Payment: {{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method }} — {{ strtoupper($order->payment_status) }}</p>
        @if ($order->notes)<p class="muted" style="margin-top:4px">Note: {{ $order->notes }}</p>@endif
        <hr>
        <p class="center">Shukriya! Please visit again.</p>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
