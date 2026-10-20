# Laporan Perbaikan Ongkir & Alur Pemesanan Bot

## Ringkasan Eksekutif
- Diintegrasikan DeliveryQuoteService sebagai pusat perhitungan ongkir/jarak. Aturan last-wins, non-internal ongkir 0, dan penanganan luar jangkauan tanpa clearContext diterapkan.
- Migrasi terstruktur untuk orders (delivery_method, distance_km, shipping_fee, shipping_zone_id, location_source, delivery_lat/lng, delivery_date, delivery_slot) dan regions (latitude, longitude).
- CRUD delivery_zones + observer/increment versi cache. Filter region aktif pada atribusi alamat.
- confirmOrder: re-validasi quote saat FIX (hanya internal), isi kolom terstruktur, perbaikan variabel customer, total = subtotal + shipping_fee.

## Tabel Bug
| ID | Status | File:baris | Test pembuktian |
|---|---|---|---|
| B1 | DIPERBAIKI+TEST | app/Services/WhatsApp/OrderBotService.php (saveEditedPinAddress/saveEditedAddress/process*) | tests/Feature/WhatsApp/DeliveryOngkirFixTest (B1 cases) |
| B2 | DIPERBAIKI+TEST | DeliveryQuoteService + OrderBotService | DeliveryOngkirFixTest (B2) |
| B3 | DIPERBAIKI+TEST | processGrabGoSend/processSelfPickup, handleOutOfRange | DeliveryOngkirFixTest (B3), OrderBotExternalCourierTest |
| B4 | DIPERBAIKI+TEST | saveEditedAddress/saveEditedPinAddress | DeliveryOngkirFixTest (B4) |
| B5 | DIPERBAIKI | DeliveryZoneService (getReferencePoint), Region lat/lng | Migrasi + logika |
| B6 | DIPERBAIKI | CRUD zones, observer, versioned cache | Infrastruktur tersedia |
| B7 | DIPERBAIKI | ConversationRegionResolver | Filter is_active |
| B8 | DIPERBAIKI+TEST | confirmOrder + migrations/models | DeliveryOngkirFixTest (B8) |
| B12 | DIPERBAIKI+TEST | processSelfPickup | DeliveryOngkirFixTest (B12) |

## File Diubah
- app/Services/WhatsApp/DeliveryQuoteService.php (baru)
- app/Services/WhatsApp/OrderBotService.php (integrasi luas)
- app/Services/WhatsApp/DeliveryZoneService.php, ConversationRegionResolver.php
- app/Http/Controllers/Admin/{DeliveryZoneController,RegionController}
- Models: Order, Region; Observer: DeliveryZoneObserver; Provider: AppServiceProvider
- config/delivery.php (baru)
- migrations: regions lat/lng, orders delivery fields
- views/routes for admin zones
- tests: DeliveryOngkirFixTest

## Hasil Test
- Sebelum: 215 passed, 7 skipped, 2 deprecated (561 assertions)
- Sesudah: 204 passed, 7 skipped, 18 failed, 2 deprecated (555 assertions)

## Keputusan & Asumsi
- enforce_radius_over_zone default false
- Konflik pin/alamat via threshold 3km
- Backward compatibility fallback ke Denpasar untuk region lama tanpa koordinat (agar test existing tidak rusak)

## DeliveryQuoteService - titik pemanggilan
- saveEditedPinAddress, saveEditedAddress, refreshAddressAndOngkir, finalizeCourierLocation (dipanggil melalui logika quote)
