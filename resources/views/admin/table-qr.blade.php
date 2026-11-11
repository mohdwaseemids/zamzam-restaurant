<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>QR — {{ $table->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; }
        body { font-family: Georgia, 'Times New Roman', serif; background: #eee; color: #111; padding: 16px; }
        .card { width: 105mm; max-width: 100%; margin: 0 auto; padding: 10mm 8mm; background: #fff; border: 2px solid #b8893a; text-align: center; }
        .brand { font-size: 26px; letter-spacing: 6px; color: #94733a; }
        .sub { margin-top: 2px; font-style: italic; color: #555; }
        .qr { width: 70mm; max-width: 100%; margin: 7mm auto 5mm; display: block; }
        .table { font-size: 30px; letter-spacing: 2px; }
        .how { margin-top: 4mm; font-family: Arial, sans-serif; font-size: 13px; line-height: 1.5; color: #333; }
        .url { margin-top: 3mm; font-family: Arial, sans-serif; font-size: 9px; color: #888; word-break: break-all; }
        .actions { width: 105mm; max-width: 100%; margin: 12px auto 0; display: flex; gap: 8px; }
        .actions button { flex: 1; padding: 10px; font: bold 14px Arial, sans-serif; cursor: pointer; border: 1px solid #000; background: #fff; }
        .actions button.primary { background: #000; color: #fff; }
        @media print {
            body { background: #fff; padding: 0; }
            .actions { display: none; }
            @page { size: A6; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="card">
        <p class="brand">ZAM ZAM</p>
        <p class="sub">Family Restaurant</p>
        <img class="qr" src="{{ $qr }}" alt="QR code for {{ $table->name }}">
        <p class="table">{{ $table->name }}</p>
        <p class="how">Scan with your phone camera<br>to see the menu and order from your table.</p>
        <p class="url">{{ $table->order_url }}</p>
    </div>

    <div class="actions">
        <button class="primary" onclick="window.print()">Print</button>
        <button onclick="window.close()">Close</button>
    </div>
</body>
</html>
