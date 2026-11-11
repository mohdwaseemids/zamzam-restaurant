@php
    $taxable = max(0, (float) $order->subtotal - (float) $order->discount - (float) $order->points_discount);
    $tax = (float) $order->tax;
    $rate = $taxable > 0 ? round($tax / $taxable * 100, 2) : 0;
    $half = round($tax / 2, 2);
    $fmt = fn ($n) => number_format((float) $n, 2);
    $seller = setting('gst_legal_name') ?: setting('site_name', 'Zam Zam Family Restaurant');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Invoice {{ $order->invoice_number }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 13px; color: #111; background: #eee; padding: 16px; }
        .page { width: 190mm; max-width: 100%; margin: 0 auto; background: #fff; padding: 12mm; }
        h1 { font-size: 20px; letter-spacing: 1px; }
        .muted { color: #555; font-size: 12px; }
        .row { display: flex; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
        .title { margin: 8mm 0 5mm; padding: 6px 0; border-top: 2px solid #111; border-bottom: 1px solid #111; text-align: center; font-weight: bold; letter-spacing: 3px; }
        table { width: 100%; border-collapse: collapse; margin-top: 5mm; }
        th, td { padding: 6px 8px; border: 1px solid #999; text-align: left; vertical-align: top; }
        th { background: #f1f1f1; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
        .r { text-align: right; white-space: nowrap; }
        .totals { width: 80mm; max-width: 100%; margin-left: auto; }
        .totals td { border: 0; padding: 4px 8px; }
        .totals .grand td { border-top: 2px solid #111; font-size: 16px; font-weight: bold; padding-top: 8px; }
        .foot { margin-top: 10mm; }
        .warn { margin: 0 auto 12px; width: 190mm; max-width: 100%; padding: 10px 14px; background: #fff3cd; border: 1px solid #e0b93b; font-size: 13px; }
        .actions { width: 190mm; max-width: 100%; margin: 12px auto 0; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 10px; font: bold 14px Arial, sans-serif; cursor: pointer; border: 1px solid #000; background: #fff; }
        .actions button.primary { background: #000; color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions, .warn { display: none; }
            .page { width: auto; padding: 0; }
            @page { size: A4; margin: 12mm; }
        }
    </style>
</head>
<body>
    @unless (setting('gstin'))
        <p class="warn">GSTIN is not set. Add it in Admin → Website settings → GST &amp; legal before giving this invoice to a customer.</p>
    @endunless

    <div class="page">
        <div class="row">
            <div>
                <h1>{{ $seller }}</h1>
                <p class="muted">{{ setting('address') }}</p>
                @if (setting('phone'))<p class="muted">Phone: {{ setting('phone') }}</p>@endif
            </div>
            <div style="text-align:right">
                <p><strong>GSTIN:</strong> {{ setting('gstin') ?: '—' }}</p>
                @if (setting('fssai_number'))<p><strong>FSSAI:</strong> {{ setting('fssai_number') }}</p>@endif
            </div>
        </div>

        <p class="title">TAX INVOICE</p>

        <div class="row">
            <div>
                <p class="muted">Billed to</p>
                <p><strong>{{ $order->customer_name }}</strong></p>
                @if ($order->phone)<p>{{ $order->phone }}</p>@endif
                @if ($order->address)<p>{{ $order->address }}</p>@endif
                @if ($order->customer_gstin)<p>GSTIN: {{ $order->customer_gstin }}</p>@endif
            </div>
            <div style="text-align:right">
                <p><strong>Invoice no.</strong> {{ $order->invoice_number }}</p>
                <p><strong>Date</strong> {{ $order->created_at->format('d-m-Y') }}</p>
                <p><strong>Order</strong> {{ $order->order_number }}</p>
                <p>{{ $order->type_label }}@if ($order->table) · {{ $order->table->name }}@endif</p>
            </div>
        </div>

        <table>
            <thead>
                <tr><th style="width:6%">#</th><th>Description</th><th style="width:12%">SAC</th><th class="r" style="width:8%">Qty</th><th class="r" style="width:14%">Rate</th><th class="r" style="width:16%">Amount</th></tr>
            </thead>
            <tbody>
                @foreach ($order->items as $item)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $item->name }}</td>
                        <td>{{ setting('gst_sac', '996331') }}</td>
                        <td class="r">{{ $item->quantity }}</td>
                        <td class="r">{{ $fmt($item->price) }}</td>
                        <td class="r">{{ $fmt($item->total) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="totals">
            <tr><td>Subtotal</td><td class="r">{{ $fmt($order->subtotal) }}</td></tr>
            @if ((float) $order->discount > 0)<tr><td>Discount{{ $order->coupon_code ? ' ('.$order->coupon_code.')' : '' }}</td><td class="r">-{{ $fmt($order->discount) }}</td></tr>@endif
            @if ((float) $order->points_discount > 0)<tr><td>Loyalty points</td><td class="r">-{{ $fmt($order->points_discount) }}</td></tr>@endif
            <tr><td><strong>Taxable value</strong></td><td class="r"><strong>{{ $fmt($taxable) }}</strong></td></tr>
            @if ($tax > 0)
                <tr><td>CGST @ {{ rtrim(rtrim(number_format($rate / 2, 2), '0'), '.') }}%</td><td class="r">{{ $fmt($half) }}</td></tr>
                <tr><td>SGST @ {{ rtrim(rtrim(number_format($rate / 2, 2), '0'), '.') }}%</td><td class="r">{{ $fmt($tax - $half) }}</td></tr>
            @endif
            @if ((float) $order->delivery_charge > 0)<tr><td>Delivery charge</td><td class="r">{{ $fmt($order->delivery_charge) }}</td></tr>@endif
            <tr class="grand"><td>Total (INR)</td><td class="r">{{ $fmt($order->total) }}</td></tr>
        </table>

        <div class="foot row">
            <div class="muted">
                <p>Payment: {{ \App\Models\Order::PAYMENT_METHODS[$order->payment_method] ?? $order->payment_method }} — {{ ucfirst($order->payment_status) }}</p>
                @if ($tax <= 0)<p>No GST charged separately on this invoice.</p>@endif
                <p>This is a computer generated invoice.</p>
            </div>
            <div style="text-align:right">
                <p>For {{ $seller }}</p>
                <p style="margin-top:14mm">Authorised signatory</p>
            </div>
        </div>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print / Save as PDF</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
