# Delivery Quote Service

## Ringkasan
`App\Services\WhatsApp\DeliveryQuoteService` menyediakan satu sumber kebenaran untuk menghitung ongkir, jarak, zona, dan status (needs_pin/out_of_range). Semua jalur penghitungan ongkir diarahkan melalui service ini.

## Aturan
- Non-internal (`self_pickup`, `grab_gosend`): ongkir selalu 0, `distance_km` dinull-kan.
- Pin lebih akurat; last-input-wins saat ada koreksi pin/alamat.
- Jika alamat tidak dikenali dan tidak ada pin → `needs_pin = true`.
- Luar radius kurir internal → `out_of_range/needs_escalation = true`, tawarkan Grab/GoSend atau Ambil Sendiri (tanpa menghapus context).
- Konflik pin vs alamat (selisih > `delivery.conflict_threshold_km`) bisa memicu konfirmasi (konfigurabel).
- Zona eksplisit menang atas tier. Cache zona invalidasi via versi (`delivery_zones_ver_{regionId}`) + Observer.

## Konfigurasi
Lihat `config/delivery.php`:
- `max_radius_km` (default 14)
- `enforce_radius_over_zone` (default false)
- `conflict_threshold_km` (default 3)
- `fee_tiers` (10k < 10km, 15k <= 14km)

## Region & Zona
- Titik referensi region bisa diset via `regions.latitude/longitude` (fallback ke slug lama jika kosong).
- `delivery_zones` dikelola via Admin CRUD (`/admin/delivery-zones`). 
- Region nonaktif tidak dipertimbangkan untuk atribusi alamat.
