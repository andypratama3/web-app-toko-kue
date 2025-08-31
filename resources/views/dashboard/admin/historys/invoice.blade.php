@php
    // Deteksi mode PDF (Dompdf)
    $isPdf = request()->routeIs('admin.historys.download');
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->invoice_number }}</title>
    @if(!$isPdf)
        <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
        <style>
            @media print {
                .no-print { display: none; }
                @page { margin: 8mm; }
                body { margin: 0 !important; }
            }
        </style>
    @else
        <style>
            @page {
                size: A4;
                margin: 6mm;
            }
            body {
                font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
                color: #000;
                margin: 0;
                padding: 0;
                background: #fff;
                font-size: 9px;
                line-height: 1.1;
            }
            .container {
                width: 100%;
                max-width: 100%;
                margin: 0;
                border: 1px solid #000;
                padding: 6px;
                background: #fff;
                box-sizing: border-box;
                height: auto;
                max-height: 45vh;
            }
            .header {
                display: table;
                width: 100%;
                margin-bottom: 8px;
            }
            .header-left {
                display: table-cell;
                vertical-align: top;
                width: 50%;
            }
            .header-right {
                display: table-cell;
                vertical-align: top;
                width: 50%;
                text-align: right;
            }
            .invoice-title {
                font-size: 18px;
                font-weight: bold;
                margin: 0 0 3px 0;
                color: #000;
            }
            .invoice-number {
                font-size: 9px;
                color: #000;
                margin: 0.5px 0;
            }
            .invoice-date {
                font-size: 9px;
                color: #000;
                margin: 0.5px 0;
            }
            .brand-kue {
                font-size: 12px;
                color: #97b67d;
                margin: 0;
                font-weight: normal;
            }
            .brand-pandan {
                font-size: 14px;
                color: #97b67d;
                font-weight: bold;
                margin: 0;
                letter-spacing: 0.5px;
            }
            .kurir-info {
                font-size: 9px;
                color: #000;
                margin: 3px 0 0 0;
            }
            .customer-section {
                margin-bottom: 8px;
            }
            .customer-label {
                font-size: 10px;
                font-weight: bold;
                margin: 0 0 1px 0;
                color: #000;
            }
            .customer-name {
                font-size: 10px;
                font-weight: bold;
                margin: 0 0 1px 0;
                color: #000;
            }
            .customer-details {
                font-size: 9px;
                margin: 0 0 0.5px 0;
                color: #000;
            }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 8px;
            }
            th, td {
                border: 1px solid #000;
                padding: 2px 4px;
                font-size: 9px;
                text-align: left;
                vertical-align: middle;
            }
            th {
                background: #f5f5f5;
                font-weight: bold;
                color: #000;
            }
            .text-center {
                text-align: center;
            }
            .text-right {
                text-align: right;
            }
            .total-row {
                font-weight: bold;
                background: #f5f5f5;
            }
            .footer-signature {
                margin-top: 8px;
                font-size: 9px;
                color: #000;
                padding-bottom: 3px;
            }
        </style>
    @endif
</head>
<body @if(!$isPdf) class="bg-white p-8 print:p-0" @endif>
    <div @if($isPdf) class="container" @else class="max-w-4xl mx-auto border-2 border-black p-8" @endif>
        <!-- Header -->
        <div @if($isPdf) class="header" @else class="flex justify-between items-start mb-8" @endif>
            <div @if($isPdf) class="header-left" @else class="flex-1" @endif>
                <h1 @if($isPdf) class="invoice-title" @else class="text-3xl font-bold mb-2" @endif>#INVOICE</h1>
                <div @if($isPdf) class="invoice-number" @else class="text-sm mb-1" @endif>{{ $order->invoice_number }}</div>
                <div @if($isPdf) class="invoice-date" @else class="text-sm" @endif>Tanggal: {{ $order->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</div>
            </div>
            <div @if($isPdf) class="header-right" @else class="text-right" @endif>
                <div @if($isPdf) class="brand-kue" @else class="text-lg" style="color: #97B67D;" @endif>Kue Ijo</div>
                <div @if($isPdf) class="brand-pandan" @else class="text-2xl font-bold" style="color: #97B67D; letter-spacing: 2px;" @endif>PANDAN ASLI</div>
                <div @if($isPdf) class="kurir-info" @else class="text-sm mt-2" @endif>Kurir: Kurir Surabaya</div>
            </div>
        </div>

        <!-- Customer Info -->
        <div @if($isPdf) class="customer-section" @else class="mb-6" @endif>
            <div @if($isPdf) class="customer-label" @else class="font-bold text-base mb-1" @endif>Kepada Yth:</div>
            <div @if($isPdf) class="customer-name" @else class="font-bold text-base mb-1" @endif>{{ $order->customer->name ?? 'Kiki' }}</div>
            <div @if($isPdf) class="customer-details" @else class="text-sm mb-1" @endif>{{ $order->customer->phone ?? '628133651455' }}</div>
            <div @if($isPdf) class="customer-details" @else class="text-sm" @endif>{{ $order->address ?? 'Jl Patimura' }}</div>
        </div>

        <!-- Products Table -->
        <table>
            <thead>
                <tr>
                    <th>Produk</th>
                    <th>Varian</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Harga</th>
                    <th class="text-right">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td>{{ $item->product_name }}</td>
                    <td>{{ $item->variant_name ?? '-' }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="total-row">
                    <td colspan="4" class="text-right"><strong>Total</strong></td>
                    <td class="text-right"><strong>Rp {{ number_format($order->total_amount, 0, ',', '.') }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <!-- Footer -->
        <div @if($isPdf) class="footer-signature" @else class="mt-6" @endif>
            <p>Hormat Kami,<br>Dian</p>
        </div>

        @if(!$isPdf)
        <div class="mt-8 flex justify-between items-center">
            <div class="no-print flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Print Invoice</button>
                <button onclick="window.close()" class="px-4 py-2 bg-red-400 text-white rounded hover:bg-gray-600">Tutup</button>
            </div>
        </div>
        @endif
    </div>
</body>
</html>
