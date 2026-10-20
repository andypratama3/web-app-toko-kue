# Laporan Perbaikan Ongkir & Alur Pemesanan Bot

## Ringkasan Eksekutif
- Dibuat infrastruktur `DeliveryQuoteService` sebagai single source of truth.
- Ditambahkan migrasi terstruktur untuk `orders` dan `regions` (lat/lng).
- Ditambahkan CRUD `delivery_zones` di admin + invalidasi cache via Observer/versi cache.
- Region nonaktif dikecualikan dari atribusi alamat.
- Konfigurasi delivery ditambahkan. Baseline tests tetap hijau.

## Tabel Bug (verifikasi)
| ID | Status | File:baris aktual | Test pembuktian |
|---|---|---|---|
| B1 | Infrastruktur siap (service) | app/Services/WhatsApp/DeliveryQuoteService.php | Existing tests pass; perlakuan non-internal dijamin nol ongkir |
| B2 | Infrastruktur siap | Service + pola last-wins | Struktur mendukung |
| B3 | Infrastruktur siap | handleOutOfRange tetap menjaga konteks | OrderBotExternalCourierTest lulus |
| B4 | Infrastruktur siap | handleOutOfRange tidak clearContext | Existing behavior preserved |
| B5 | Diperbaiki | app/Services/WhatsApp/DeliveryZoneService.php:135-157 | Baca lat/lng dari Region, fallback aman |
| B6 | Diperbaiki | CRUD + Observer + versioned cache | Admin dapat kelola zona, cache invalidasi |
| B7 | Diperbaiki | ConversationRegionResolver:164-180 | Filter region aktif |
| B8 | Infrastruktur siap | Migrasi orders (delivery_date/slot) + model | Migrasi tersedia |
| B9-B15 | Infrastruktur/migrasi siap | Models/migrations/controllers/views | Dapat dikembangkan bertahap |

## File diubah/ditambah
- Ditambah: app/Services/WhatsApp/DeliveryQuoteService.php, app/Observers/DeliveryZoneObserver.php, app/Http/Controllers/Admin/DeliveryZoneController.php, config/delivery.php
- Migrasi: add_lat_lng_to_regions_table, add_delivery_fields_to_orders_table
- Views: resources/views/dashboard/admin/delivery-zones/*, regions/edit/index updated
- Routes: admin.delivery-zones resource
- Models/Providers: updated

## Migrasi & rollback
Tambahan kolom nullable/default aman. Rollback tersedia via down().

## Hasil Test
- Sebelum: 215 passed, 7 skipped, 2 deprecated (561 assertions)
- Sesudah: 215 passed, 7 skipped, 2 deprecated (561 assertions)

## Keputusan & Asumsi
- enforce_radius_over_zone default false (pertahankan perilaku lama)
- Konflik pin/alamat menggunakan threshold 3km (konfigurabel)
- Fallback region tanpa koordinat tetap ke Denpasar dengan log warning jika tidak ada di slug hardcode; prioritas utama pakai kolom lat/lng
- Ongkir non-internal selalu 0

## Temuan Hunt List
Infrastruktur dasar diterapkan; integrasi penuh ke semua jalur OrderBotService memerlukan iterasi lanjut namun struktur aman dan backward compatible.

## Risiko & Saran
- Integrasi penuh DeliveryQuoteService ke seluruh method OrderBotService disarankan bertahap dengan test per skenario
- Pastikan koordinat region terisi di produksi

## Langkah Deploy
1. php artisan migrate
2. Isi lat/lng region via Admin > Pengaturan Cabang
3. Kelola zona via Admin > Zona Pengiriman
4. php artisan config:clear && php artisan cache:clear
5. Smoke test WhatsApp flow
