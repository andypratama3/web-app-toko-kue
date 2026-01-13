<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Export Peforma Kurir</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
        }

        .page-break {
            page-break-before: always;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: center;
        }

        th {
            background: #eee;
        }

        h2 {
            margin-bottom: 0;
        }

        .subtitle {
            margin-top: 0;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <h2>Ranking Peforma Kurir</h2>
    <div class="subtitle">Tanggal <b>{{ $daterange }}</b></div>

    @forelse($ranking as $row)
        <table>
            <thead>
                <tr>
                    <th>Rank</th>
                    <th>Nama Kurir</th>
                    <th>Total Customer</th>
                    <th>Jumlah Pesanan Selesai</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $row['rank'] }}</td>
                    <td>{{ $row['nama_kurir'] }}</td>
                    <td>{{ $row['total_customer'] }}</td>
                    <td>{{ $row['jumlah_order'] }}</td>
                    <td>{{ $row['total'] }}</td>
                </tr>
            </tbody>
        </table>

        <h3>Rincian Pesanan {{ $row['nama_kurir'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Tanggal</th>
                    <th>Invoice Number</th>
                    <th>Nama Customer</th>
                    <th>Detail Pesanan</th>
                    <th>total</th>
                </tr>
            </thead>
            <tbody>

                @forelse ($row["orders"] as $order)
                    <tr>
                        <td>{{ $loop->iteration }}</td>
                        <td>{{ $order->created_at }}</td>
                        <td>{{ $order->invoice_number }}</td>
                        <td>{{ $order->customer->name }}, {{ $order->customer->company_name }}</td>
                        <td style="padding:0;">
                            <table width="100%" style="border-collapse:collapse; font-size:11px;">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td style="border:none; text-align:left; padding:4px;">
                                            <strong>{{ $item->product->name }}</strong>
                                        </td>
                                        <td style="border:none; text-align:left; padding:4px;">
                                            {{ $item->variant_name ?? '-' }}
                                        </td>
                                        <td style="border:none; text-align:right; padding:4px; white-space:nowrap;">
                                            x{{ $item->quantity }}
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                        <td>{{ $order->total_amount }}</td>
                    </tr>
                @empty
                    <p>Tidak ada transaksi</p>
                @endforelse
            </tbody>
        </table>
        @if (!$loop->last)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>

</html>
