<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $order->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print { display: none; }
            @page { margin: 0; }
            body { margin: 0 !important; }
        }
    </style>
</head>
<body class="bg-white p-8 print:p-0">
    <div class="max-w-2xl mx-auto border p-8 rounded shadow">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-2xl font-bold">INVOICE</h1>
                <p class="text-sm text-gray-600"><span class="font-mono">{{ $order->invoice_number }}</span></p>
                <p class="text-sm text-gray-600">Tanggal: {{ $order->created_at->setTimezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</p>
            </div>
            <div class="text-right">
                <div>
                    <span class="block text-xl font-normal leading-tight" style="font-family:serif; letter-spacing:0.5px; color:#97b67d;">Kue Ijo</span>
                    <span class="block text-3xl font-extrabold tracking-wider mt-0 -mb-2" style="font-family:serif; letter-spacing:2px; color:#97b67d;">PANDAN ASLI</span>
                </div>
                <p class="text-sm mt-2">Kurir: {{ $order->createdBy->name ?? '-' }}</p>
            </div>
        </div>
        <div class="mb-6">
            <h3 class="font-semibold">Kepada Yth:</h3>
            <p class="text-lg font-bold">{{ $order->customer->name ?? '-' }}</p>
            <p class="text-sm">{{ $order->customer->phone ?? '-' }}</p>
            <p class="text-sm">{{ $order->address ?? '-' }}</p>
        </div>
        <table class="w-full mb-6 text-sm border">
            <thead>
                <tr class="bg-gray-100">
                    <th class="border px-2 py-1">Produk</th>
                    <th class="border px-2 py-1">Varian</th>
                    <th class="border px-2 py-1">Qty</th>
                    <th class="border px-2 py-1">Harga</th>
                    <th class="border px-2 py-1">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                <tr>
                    <td class="border px-2 py-1">{{ $item->product_name }}</td>
                    <td class="border px-2 py-1">{{ $item->variant_name ?? '-' }}</td>
                    <td class="border px-2 py-1 text-center">{{ $item->quantity }}</td>
                    <td class="border px-2 py-1 text-right">Rp {{ number_format($item->price, 0, ',', '.') }}</td>
                    <td class="border px-2 py-1 text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="4" class="border px-2 py-1 text-right font-bold">Total</td>
                    <td class="border px-2 py-1 text-right font-bold">Rp {{ number_format($order->total_amount, 0, ',', '.') }}</td>
                </tr>
            </tfoot>
        </table>
        <div class="mb-4">
            <p class="text-xs text-gray-500">Hormat Kami,<br>Dian</span></p>
        </div>
        <div class="mt-8 flex justify-between items-center">
            <div class="no-print flex gap-2">
                <button onclick="window.print()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Print Invoice</button>
                <button onclick="window.close()" class="px-4 py-2 bg-red-400 text-white rounded hover:bg-gray-600">Tutup</button>
            </div>
        </div>
    </div>
</body>
</html>
