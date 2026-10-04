<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
@page { size: A4; margin: 6mm; }
body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #000; }
h1 { font-size: 16px; margin-bottom: 4px; }
.header { border-bottom: 1px solid #000; padding-bottom: 6px; margin-bottom: 10px; display: flex; justify-content: space-between; }
table { width: 100%; border-collapse: collapse; }
th, td { border: 1px solid #333; padding: 3px; text-align: left; font-size: 9px; }
th { background: #f0f0f0; }
.bot-label { color: #c00; font-weight: bold; font-size: 11px; }
</style>
</head>
<body>
<div class="header">
<div>
<h1>REKAP PESANAN BOT</h1>
<div class="bot-label">Channel: WhatsApp (Bot) • Status: Diverifikasi Admin</div>
@if($start && $end)
<div>Range: {{ $start }} s/d {{ $end }}</div>
@endif
<div>Invoice: {{ $order->invoice_number }}</div>
<div>Tanggal: {{ $order->created_at->format('d/m/Y H:i') }}</div>
</div>
<div style="text-align:right;">
<strong>KUE PANDAN ASLI</strong><br>Rekap Bot Order
</div>
</div>

<div style="margin-bottom:8px;">
<strong>Pelanggan:</strong> {{ $order->customer->name ?? '-' }}<br>
<strong>Telepon:</strong> {{ $order->customer->phone ?? $order->phone ?? '-' }}<br>
<strong>Alamat:</strong> {{ $order->address ?? '-' }}<br>
<strong>Metode Bayar:</strong> {{ $order->payment_method ?? '-' }}
</div>

<table>
<thead><tr><th>Produk</th><th>Varian</th><th>Qty</th><th>Harga</th><th>Subtotal</th></tr></thead>
<tbody>
@foreach($order->items as $item)
<tr>
<td>{{ $item->product_name }}</td>
<td>{{ $item->variant_name ?? '-' }}</td>
<td>{{ $item->quantity }}</td>
<td>Rp {{ number_format($item->price,0,',','.') }}</td>
<td>Rp {{ number_format($item->subtotal,0,',','.') }}</td>
</tr>
@endforeach
</tbody>
<tfoot>
<tr style="font-weight:bold; background:#eee;">
<td colspan="4" style="text-align:right;">Total</td>
<td>Rp {{ number_format($order->total_amount,0,',','.') }}</td>
</tr>
</tfoot>
</table>
</body>
</html>
