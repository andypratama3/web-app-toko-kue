# Release Notes — Revisi Chatbot WhatsApp

Status: kode selesai di branch `rev/fase-10-qa`, belum di-merge/push.
Tag: `revisi-selesai-kode` (dibuat setelah QA akhir).

## Ringkasan

Revisi R-01…R-18 diterapkan bertahap pada bot WhatsApp "Kue Pandan Asli":
katalog dan harga per kota, alur pesanan linear, nomor penerima, notifikasi
admin/owner, dan pembersihan kata "cabang" dari teks pelanggan.

## Matriks revisi

| Kode | Ringkas | Status |
|---|---|---|
| R-01 | Produk/harga Surabaya & Malang tercampur | ✅ Selesai |
| R-02 | Foto produk tidak tampil | ✅ Selesai |
| R-03 | Alur pesanan membingungkan | ✅ Selesai |
| R-04 | Awalan "⭐ List produk" | ✅ Selesai |
| R-05 | Nomor lama masih ditanya kota | ✅ Selesai |
| R-06 | Menu harga/produk pakai pricelist lama | ✅ Selesai |
| R-07 | Catatan klien terpotong | ⛔ OPEN — butuh klarifikasi |
| R-08 | Tidak ada pilihan menu/kategori | ✅ Selesai |
| R-09 | Judul katalog harus memuat nama kota | ✅ Selesai |
| R-10 | Prompt setelah masuk keranjang | ✅ Selesai |
| R-11 | Pilihan ganti kota mudah dijangkau | ✅ Selesai |
| R-12 | Tanda "-" pada sub produk | ✅ Selesai |
| R-13 | Tumpeng/kue mix/hampers: cukup isi | ✅ Selesai |
| R-14 | Produk baru | ✅ Selesai (data sudah ada di production) |
| R-15 | Harga khusus Surabaya/Malang | 🟡 Infra + import siap; data belum ada |
| R-16 | Hilangkan kata "cabang" | ✅ Selesai (pelanggan, admin, owner) |
| R-17 | Nomor admin Malang & Surabaya | ✅ Selesai (via `BOT_ADMIN_PHONE`/`owner_phone`) |
| R-18 | Contoh format lengkap + no penerima | ✅ Selesai |

## Perubahan utama

- `ConversationRegionResolver` menentukan kota; `PriceResolver` menentukan harga kota.
- `ProductListRenderer` menjadi satu sumber katalog, kategori, dan pricelist.
- Alur: kota → katalog → produk → keranjang → nama → alamat → nomor penerima →
  tanggal → pengiriman → ringkasan/FIX → pembayaran.
- `footerFor()` menjadi sumber tunggal footer; state nonaktif hanya menawarkan `PESAN`.
- `AdminContactResolver` menyatukan nomor admin/owner (`BOT_ADMIN_PHONE` → `owner_phone`).
- `orders.recipient_phone` menyimpan nomor penerima dan tampil di ringkasan/notifikasi.
- Command baru: `php artisan bot:import-prices {file}` (dry-run default, `--apply` opsional).

## Verifikasi

- `php artisan test --compact` → **318 passed, 7 skipped, 995 assertions**.
- Regression review Fase 9 → **13 test** di `tests/Feature/WhatsApp/OrderBotReviewRegressionTest.php`.
- Test import harga → **6 test** di `tests/Feature/Console/ImportVariantPricesCommandTest.php`.
- Pint file yang disentuh → **PASS**.
- Dua deprecation PHP 8.5 (`PDO::MYSQL_ATTR_SSL_CA`, `ReflectionMethod::setAccessible()`) pre-existing.

## Checklist rilis

- [ ] Backup database production.
- [ ] Jalankan migrasi yang masih pending (termasuk `is_global`, `product_variant_prices`, `recipient_phone`).
- [ ] Isi `BOT_ADMIN_PHONE` atau `regions.owner_phone`.
- [ ] Import harga dengan dry-run, periksa, lalu `--apply` bila data sudah disetujui.
- [ ] Kirim pesan tes owner dari Pengaturan Kota.
- [ ] Uji alur `PESAN` sampai `ORDER_CONFIRMED` di nomor uji.
- [ ] Merge/push dan tag sesuai persetujuan pemilik.

## Catatan

- Tidak ada push, merge, atau migrasi production yang dilakukan dalam fase ini.
- R-07 tetap OPEN; jangan menebak maksud catatan yang terpotong.
- R-15 hanya menunggu data harga per varian dari pemilik.
