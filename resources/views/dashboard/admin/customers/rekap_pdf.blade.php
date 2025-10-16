<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Rekap Order Customer</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 6px; text-align: left; }
        th { background: #eee; }
    </style>
</head>
<body>
    <h2>Rekap Order Customer</h2>
    <p><strong>Nama Customer:</strong> {{ $customer->name }}</p>
    <p><strong>Perusahaan:</strong> {{ $customer->company_name }}</p>
    <p><strong>Alamat:</strong> {{ $customer->address }}</p>
    <p><strong>Rentang Tanggal:</strong> {{ $start }} s/d {{ $end }}</p>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Produk</th>
                <th>Varian</th>
                <th>Jumlah Dipesan</th>
                <th>Jumlah Retur</th>
                <th>Selisih</th>
            </tr>
        </thead>
        <tbody>
            @forelse($produkDipesan as $i => $row)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ $row['product_name'] }}</td>
                    <td>{{ $row['variant_name'] ?? '-' }}</td>
                    <td>{{ $row['dipesan'] }}</td>
                    <td>{{ $row['retur'] }}</td>
                    <td>{{ $row['selisih'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align:center;">Tidak ada data order pada rentang tanggal ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
