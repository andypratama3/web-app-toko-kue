<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<title>Export Peforma Kurir</title>
	<style>
		body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
		table { border-collapse: collapse; width: 100%; margin-top: 20px; }
		th, td { border: 1px solid #333; padding: 6px 8px; text-align: center; }
		th { background: #eee; }
		h2 { margin-bottom: 0; }
		.subtitle { margin-top: 0; font-size: 14px; }
	</style>
</head>
<body>
	<h2>Ranking Peforma Kurir</h2>
	<div class="subtitle">Bulan: <b>{{ $bulan }}</b></div>
	<table>
		<thead>
			<tr>
				<th>Rank</th>
				<th>Nama Kurir</th>
				<th>Total Customer</th>
				<th>Jumlah Pesanan Selesai</th>
			</tr>
		</thead>
		<tbody>
			@forelse($ranking as $row)
			<tr>
				<td>{{ $row['rank'] }}</td>
				<td>{{ $row['nama_kurir'] }}</td>
				<td>{{ $row['total_customer'] }}</td>
				<td>{{ $row['jumlah_order'] }}</td>
			</tr>
			@empty
			<tr>
				<td colspan="4">Belum ada data peforma kurir bulan ini.</td>
			</tr>
			@endforelse
		</tbody>
	</table>
</body>
</html>
