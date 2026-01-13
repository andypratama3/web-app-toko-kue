<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Export Peforma Kurir</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #222;
            line-height: 1.4;
        }

        h2 {
            margin: 0 0 4px 0;
            font-size: 18px;
            text-align: center;
        }

        h3 {
            margin: 25px 0 8px 0;
            font-size: 14px;
            border-left: 4px solid #444;
            padding-left: 8px;
        }

        .subtitle {
            text-align: center;
            font-size: 12px;
            margin-bottom: 15px;
        }

        .page-break {
            page-break-before: always;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        th {
            background-color: #f2f2f2;
            border: 1px solid #555;
            padding: 8px;
            font-weight: bold;
            text-align: center;
            font-size: 11px;
        }

        td {
            border: 1px solid #555;
            padding: 7px 8px;
            vertical-align: top;
        }

        .text-left {
            text-align: left;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Ranking summary table */
        .summary-table td {
            text-align: center;
            font-weight: 500;
        }

        /* Order detail table */
        .order-table th {
            font-size: 10.5px;
        }

        .order-table td {
            font-size: 10.5px;
        }

        /* Nested items table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        .items-table td {
            border: none;
            padding: 3px 4px;
        }

        .items-table tr:not(:last-child) td {
            border-bottom: 0.5px dashed #ccc;
        }

        .items-product {
            font-weight: bold;
        }

        .items-variant {
            color: #666;
            font-size: 9.5px;
        }

        .items-qty {
            text-align: right;
            white-space: nowrap;
        }

        /* Prevent row breaking in PDF */
        tr {
            page-break-inside: avoid;
        }
    </style>
</head>

<body>
    <h2>Ranking Peforma Kurir</h2>
    <div class="subtitle">Tanggal <b>{{ $daterange }}</b></div>

    @forelse($ranking as $row)
        <table class="summary-table">
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
        <table class="order-table">
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
                            <table class="items-table">
                                @foreach ($order->items as $item)
                                    <tr>
                                        <td class="items-product">{{ $item->product->name }}</td>
                                        <td class="items-variant">{{ $item->variant_name ?? '-' }}</td>
                                        <td class="items-qty">x{{ $item->quantity }}</td>
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
