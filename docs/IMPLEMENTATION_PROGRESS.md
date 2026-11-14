# Implementasi Revisi Chatbot — Progress

Dokumen kerja untuk revisi chatbot WhatsApp "Kue Pandan Asli".
Sumber kebenaran persyaratan: [`REVISI_KLIEN.md`](REVISI_KLIEN.md).
Temuan kode: [`AUDIT_REVISI_CHATBOT.md`](AUDIT_REVISI_CHATBOT.md).

Aturan kerja: **satu fase per permintaan**, berhenti setelah tiap fase,
commit per fase di branch `rev/fase-N-<slug>`.

## Status fase

| Fase | Isi | Revisi | Status |
|---|---|---|---|
| 0 | Baseline: test karakterisasi, `bot:audit-data`, dokumen progress | — | ✅ Selesai |
| 1 | Foto produk konsisten (disk, helper URL, placeholder) | R-02 | ✅ Selesai |
| 2 | Konsep kota: deteksi otomatis, `GANTI KOTA`, tanpa tanya ulang | R-05, R-11 | ✅ Selesai |
| 3 | Harga per kota + pemisahan produk/kota (`is_global`, `PriceResolver`) | R-01, R-06, R-15 | ✅ Selesai |
| 4 | Renderer daftar produk tunggal (judul berkota, sub-produk `-`, isi saja) | R-04, R-09, R-12, R-13 | ✅ Selesai |
| 5 | Menu kategori dinamis dari DB | R-08 | ✅ Selesai |
| 6 | Setelah masuk keranjang: ringkasan + "Mau pesan apa?" | R-10 | ✅ Selesai |
| 7 | Wording "cabang" → nama kota, satu nomor admin | R-16 | ✅ Selesai (teks pelanggan) |
| 8 | Nomor HP penerima + contoh format lengkap | R-18 | ✅ Selesai (form_step 6 + orders.recipient_phone) |
| 9 | Penyederhanaan alur pesanan | R-03, R-07 | ⬜ Belum |
| 10 | Import produk/harga (data pemilik) + QA & rilis | R-14, R-15 | ⬜ Belum |

## Status revisi

| Kode | Ringkas | Fase | Status |
|---|---|---|---|
| R-01 | Produk/harga Surabaya & Malang tercampur | 3 | ✅ Selesai |
| R-02 | Foto produk tidak tampil | 1 | ✅ Selesai |
| R-03 | Alur pesanan membingungkan | 9 | ⬜ Belum |
| R-04 | Awalan "⭐ List produk" | 4 | ✅ Selesai |
| R-05 | Nomor lama masih ditanya kota | 2 | ✅ Selesai |
| R-06 | Menu harga/produk pakai pricelist lama | 3, 4 | ✅ Selesai (PriceResolver + render tunggal) |
| R-07 | Catatan terpotong | 9 | ⛔ OPEN — butuh klarifikasi klien |
| R-08 | Tidak ada pilihan "menu"/kategori | 5 | ✅ Selesai (kategori dari DB) |
| R-09 | Judul katalog harus memuat nama kota | 4 | ✅ Selesai |
| R-10 | Prompt "Mau pesan apa?" setelah masuk keranjang | 6 | ✅ Selesai |
| R-11 | Pilihan ganti kota mudah dijangkau | 2 | ✅ Selesai |
| R-12 | Tanda "-" pada sub produk | 4 | ✅ Selesai |
| R-13 | Tumpeng/kue mix/hampers: keterangan cukup isi | 4 | ✅ Selesai (description_mode di config/bot.php) |
| R-14 | Produk baru | 10 | ✅ Selesai (data sudah ada di production) |
| R-15 | Harga khusus Surabaya | 3, 10 | 🟡 Infra selesai — data harga belum ada |
| R-16 | Hilangkan kata "cabang" | 7 | ✅ Selesai (dihapus dari semua teks pelanggan) |
| R-17 | Nomor admin Malang & Surabaya | 7 | ✅ Selesai (+62 851-1365-8557 — Kue Pandan Asli) |
| R-18 | Contoh format lengkap + no penerima | 8 | ✅ Selesai (form_step 6 + orders.recipient_phone) |

## Keputusan desain final

| Kode | Keputusan |
|---|---|
| D1 | Satu nomor admin untuk semua kota via `BOT_ADMIN_PHONE`; `regions.owner_phone` hanya fallback. |
| D2 | `ConversationRegionResolver` satu-satunya penentu kota; override manual hanya lewat `GANTI KOTA`. |
| D3 | Harga per kota lewat tabel `product_variant_prices` + `PriceResolver`; produk tidak diduplikasi. |
| D4 | Produk lintas kota lewat `products.is_global`; semua `orWhereNull('region_id')` dihapus. |
| D5 | Satu `ProductListRenderer`; katalog penuh / ringkas / pricelist hanya beda `mode`. |
| D6 | Judul list: `⭐ *List Produk — Kue Pandan Asli {Kota}*`. |
| D7 | Kata "cabang" dihapus dari semua teks ke pelanggan/admin/pemilik. |
| D8 | Sub-produk diberi awalan `-`. |
| D9 | Tumpeng, kue mix, hampers: keterangan hanya isi (via `config/bot.php` `description_mode`, bisa dibalik). |
| D10 | R-07 tidak diimplementasikan sampai klien melengkapi catatan. |
| D11 | R-14 & R-15: bangun infrastruktur + template CSV; data diisi pemilik, tidak ditebak. |

## Perbaikan konsistensi katalog (audit follow-up, pra-Fase 4)

Sebelum Fase 4 (`ProductListRenderer`) dirilis, perbaikan konsistensi berikut diterapkan pada
`app/Services/WhatsApp/OrderBotService.php` agar daftar produk tidak kadang berbeda antar state:

- `sendProductCatalog:2800` — `orderBy('id')` sehingga penomoran deterministik.
- `sendProductsByCategory:2845` — `orderBy('id')` sehingga penomoran deterministik.
- `sendPricelist:2921` — `orderBy('id')`; `catalog_ids` dikosongkan (`:2948`) agar angka yang
  diketik setelah HARGA tidak menunjuk produk dari daftar lama.
- `sendCompactCatalog:3005` — pilihan produk lama dibersihkan saat katalog pilihan dikirim.
- `sendProductCatalog` / `sendProductsByCategory` / `sendCompactCatalog` / `sendPricelist` —
  `selected_product_*` dan `pending_options` dibersihkan agar pilihan produk lama tidak tumpang
  tindih daftar baru (mencegah PESAN setelah kategori memilih produk sebelumnya).
- `findProductByKeyword:3983` → men-delegasikan ke `resolveProduct:4059` (satu matcher deterministik:
  nama/tag, urut `LENGTH(name)`); perilaku wildcard (`%`/`_`) tetap aman.

**Tes baru**: `OrderBotStateTransitionTest::test_catalog_order_by_id_deterministic`,
`test_pricelist_clears_catalog_ids`, `test_category_clears_old_product_selection`,
`test_find_and_resolve_product_return_same`.

Suite: **283 passed, 7 skipped, 769 assertions** (2 deprecation notice PHP 8.5, bukan kegagalan). Pint 7 file PASS.

---

## Implementasi Fase 4 (R-04, R-09, R-12, R-13)

**File baru:**
- `app/Services/WhatsApp/ProductListRenderer.php` — satu renderer untuk mode
  `catalog`, `compact`, `pricelist`, `by_category`. Semua mode pakai
  `orderBy('id')` (deterministik), `visibleInRegion()` (R-01/D4), dan judul
  `"⭐ *List Produk — Kue Pandan Asli {Kota}*"` (R-04, R-09). Sub-produk
  awalan `-` (R-12). Deskripsi via `config('bot.description_mode')`
  (`full`/`only_after_berisi`/`off`, R-13). `catalog_ids` selalu disimpan;
  `selected_product_*` dibersihkan setiap daftar baru dikirim.
- `config/bot.php` — `description_mode` + `bot_name`.

**File diubah:**
- `app/Services/WhatsApp/OrderBotService.php` — 4 renderer lama (`sendProductCatalog`,
  `sendProductsByCategory`, `sendPricelist`, `sendCompactCatalog`) di-delegasikan
  ke `ProductListRenderer`. `clearContext()` diganti `resetContextPreservingCity()`
  (Fase 2-3 rekonstruksi). Semua `orWhereNull('region_id')` diganti
  `visibleInRegion()` (D4). `isChangeCityCommand`/`handleChangeCityCommand`
  ditambahkan (Fase 2 rekonstruksi). `pruneCartForRegion()` ditambahkan untuk
  buang item keranjang non-lintas-kota saat ganti kota. Teks "Cabang" → "Kota"
  di `sendWelcomeMessage` dan `askBranchSelection` (R-16 parsial).
- `tests/Feature/WhatsApp/OrderBotCharacterizationTest.php` — update ekspektasi
  judul katalog ke "List Produk — Kue Pandan Asli".

---

## Implementasi Fase 5 (R-08) — Kategori dinamis dari DB

**File baru:** —

**File diubah:**
- `app/Services/WhatsApp/OrderBotService.php` — `sendProductCategories()` sekarang
  build section dari tabel `categories` (slug sebagai ID `cat_{slug}`) bukan
  hardcoded array. `handleMenuSelection()` mencocokkan input user dengan nama/slug
  kategori dari DB.
- `app/Jobs/ProcessWhatsAppWebhookJob.php` — `normalizeInteractiveInput()` build
  mapping `cat_{slug}` → `slug` dari tabel `categories` secara dinamis.

**Tes:** Tidak ada tes baru (tes existing mencakup kategori yang sudah ada di seeder).

---

## Implementasi Fase 6 (R-10) — "Mau pesan apa?" setelah keranjang

**File baru:** —

**File diubah:**
- `app/Services/WhatsApp/OrderBotService.php` — `askAddMore()` sekarang menyertakan
  prompt `"🛒 *Mau pesan apa?"` setelah konfirmasi masuk keranjang, konsisten dengan
  judul compact catalog.

---

## Implementasi Fase 7 (R-16) — Hapus kata "cabang" dari teks pelanggan

**File baru:** —

**File diubah:**
- `app/Services/WhatsApp/OrderBotService.php` — semua kata "cabang" di teks
  customer-facing diganti dengan "kota":
  - `sendWelcomeMessage`: "Cabang {nama}" → "Kota pengiriman: *{nama}*"
  - `askBranchSelection`: "Pilih Cabang Pengiriman" → "Pilih Kota Pengiriman"
  - `handleBranchChoice`: "pilihan cabang dibatalkan" → "pilihan kota dibatalkan"
  - Footer list: "nama cabang" → "nama kota"
  - Fallback default: 'cabang kami' → 'kota kami'
  - `handleCityMention`: "dari cabang" → "dari *{nama}*", "beberapa cabang" → "beberapa kota", "admin cabang" → "admin"

---

## Implementasi Fase 8 (R-18) — Nomor HP penerima

**File baru:**
- `database/migrations/2026_11_14_000003_add_recipient_phone_to_orders_table.php` —
  menambah kolom `orders.recipient_phone` (nullable, reversible).

**File diubah:**
- `app/Services/WhatsApp/OrderBotService.php`:
  - `processAddMoreStep()`: setelah LANJUT dengan cart non-kosong → `form_step 6`
    (nomor penerja) bukan `form_step 1` (nama).
  - `processPhoneStep()` (baru): validasi nomor HP (minimal 10 digit setelah
    normalisasi), simpan ke `context.recipient_phone`, lanjut ke `form_step 1` (nama).
  - `handleOrderForm()`: match case `6 => $this->processPhoneStep()`.
  - `confirmOrder()`: simpan `$context['recipient_phone']` ke `orders.recipient_phone`.

**Tes baru/update:** Semua test flow order diupdate untuk menyertakan langkah nomor
penerja antara LANJUT dan nama.

---

## Pertanyaan terbuka untuk klien

1. **R-07** — catatan terpotong: "Pada saat memilih *pesan*" … apa yang terjadi/Diinginkan?
2. **R-04** — "⭐List produk" itu judul daftar produk, atau tombol menu?
3. **R-13** — konfirmasi: untuk tumpeng/kue mix/hampers, deskripsi hanya berisi daftar isi (tanpa narasi)?
4. **R-12** — awalan `-` untuk sub-produk: menambah, mengganti, atau memperbaiki yang sudah ada?
5. **R-15** — daftar harga khusus Surabaya (nama produk, varian, harga per kota) untuk diisi ke CSV.
6. **D4** — produk mana yang dijual di kedua kota dengan harga sama (→ `is_global`)? Sementara semua produk lama `region_id NULL` sudah di-backfill `is_global = true`; koreksi bila ada yang seharusnya khusus satu kota.

## Baseline Fase 0

- Commit awal: `064ab4d` (docs revisi + audit), branch `rev/fase-0-baseline`.
- Test suite awal: **243 passed, 7 skipped** (2 deprecation notice PHP 8.5, bukan kegagalan).
- Test karakterisasi baru: `tests/Feature/WhatsApp/OrderBotCharacterizationTest.php` — 7 skenario
  (sapaan, PESAN, PRODUK, HARGA, pilih varian → keranjang, LANJUT → ringkasan, BATAL).
- Helper test: `tests/Support/BuildsBotWorld.php`, `tests/Support/RecordingWhatsappMetaService.php`.
- Command audit: `php artisan bot:audit-data` (read-only).

### Temuan `bot:audit-data` (data lokal saat ini)

- Region hanya **Malang**, `owner_phone` **kosong**.
- **14 produk** `region_id` NULL → muncul di semua kota (akar R-01).
- Tidak ada varian tanpa harga.
- Semua produk punya `image_path`; file legacy `assets/homepage/product/*.jpg` **ada** di `public/`
  (14 file) — klaim "14 file foto tidak ada" di audit adalah false positive karena audit hanya
  memeriksa disk `public` (`storage/app/public`), bukan `public/`.

## Fase 1 — Foto produk (R-02)

**Akar masalah**: admin memakai `Storage::url($product->image_path)` sehingga path legacy
`assets/homepage/product/x.jpg` menjadi `/storage/assets/...` (disk `public` kosong) → gambar rusak.
File aslinya ada di `public/assets/homepage/product/`.

**Perubahan**

- `app/Support/ProductImage.php` (baru): `exists()`, `url()`, `placeholderUrl()` menangani dua
  konvensi path (legacy `public/` dan upload `storage/app/public`) + URL eksternal `http(s)`.
- `app/Models/Product.php`: accessor `image_url` → `ProductImage::url($this->image_path)`.
- Admin `products/index|show|edit.blade.php`: pakai `{{ $product->image_url }}`.
- `OrderBotService::sendProductDetail`: kirim foto hanya bila `ProductImage::exists()`, URL dari
  `$product->image_url`; gagal kirim foto → log warning, lanjut teks.
- `bot:audit-data` memakai `ProductImage::exists()` (audit foto kini benar).
- `public/images/placeholder-product.png` (baru, 800×800).
- `.env.example`: `FILESYSTEM_DISK=public`.
- `resources/views/dashboard/admin/products/show.blade.php:29`: perbaikan bug lama —
  `route('admin.dashboard')` tanpa parameter `region` → 500.
- `docs/DEPLOY_FOTO.md` (baru): `storage:link`, `FILESYSTEM_DISK`, `APP_URL`, dua lokasi foto,
  verifikasi.
- Test: `tests/Unit/Support/ProductImageTest.php` (11), `tests/Feature/Admin/ProductImageRenderingTest.php` (3).

**Catatan produksi**: `public/storage` lokal berupa direktori biasa (bukan symlink) dan
`storage/app/public` kosong → upload baru 404 sampai `rm -rf public/storage && php artisan storage:link`.

## Fase 2 — Konsep kota (R-05, R-11)

**Akar masalah**: pilihan kota hilang setiap kali context direset (stale/reset keranjang),
jadi nomor lama ditanya kota lagi; dan tidak ada perintah pindah kota dari state mana pun
→ customer harus scroll ke atas.

**Perubahan**

- `resetContextPreservingCity()` (baru, `OrderBotService.php:712`): menggantikan `clearContext()`
  pada semua jalur reset — `branch_source` (sumber kunci kota) ikut selamat, sehingga
  `ConversationRegionResolver::isLocked()` tetap benar setelah reset.
- `askBranchSelection()` kini titik keputusan: kota sudah terkunci (`isLocked` + region ada)
  → lewati pertanyaan, langsung `requestDeliveryLocation()` (R-05). Guard serupa di
  `beginOrderFlow()` dihapus karena duplikat; `PESAN` memanggil `askBranchSelection()` langsung.
- `sendCitySelection()` (baru): menampilkan daftar kota selalu (tanpa guard lock) — dipakai
  `GANTI KOTA` agar daftar tetap muncul walau kota sedang terkunci (R-11).
- `isChangeCityCommand()` / `handleChangeCityCommand()` + bloknya di `routeMessage():111`:
  perintah global yang diecek sebelum `checkGlobalEscape` dan guard field form, sehingga tidak
  ditelan sebagai jawaban langkah. Terima `kota`, `ganti kota`, `ubah kota`, `pindah kota`,
  `ganti lokasi`. Balasan: `Kota pengiriman saat ini: *{kota}*.` lalu `sendCitySelection()`.
- `pruneCartForRegion()` (`OrderBotService.php:3324`): buang hanya baris keranjang yang tidak
  berlaku di kota baru + umumkan `Item berikut sudah tidak tersedia dan dikeluarkan: …`
  (produk lintas kota tetap ada). Dipakai `sendOrderSummary()` dan `handleBranchChoice()`.
- Teks alur kota tanpa "cabang": `sendCitySelection` (`📍 *Pilih Kota Pengiriman*`, footer
  "kota", placeholder "Ketik angka atau nama kota"), `handleBranchChoice`, `handleCityMention`
  (semua cabang → kota; "kota lain" kini menawarkan `GANTI KOTA`), `buildCurrentStepGuide`.
- `sendWelcomeMessage`: `Kota pengiriman: *{kota}*` (mengganti baris `Cabang {kota}` +
  `📍 Lokasi:` yang redundan) dan command bar memuat `GANTI KOTA`.
- Test baru `tests/Feature/WhatsApp/OrderBotCitySwitchTest.php` (7 test): daftar GANTI KOTA dari
  welcome, kerja dari state form, kota terkunci tetap bisa diganti, prune keranjang, nomor lama
  tidak ditanya ulang, reset stale mempertahankan kota, welcome menyebut kota tanpa "Cabang ".
  `OrderBotCharacterizationTest:91` → assertion `Pilih Kota Pengiriman`.

**Pembagian scope de-cabang**: Fase 2 menguasai permukaan UX pilih/pindah kota di atas.
Sisa kemunculan "cabang" ditangani **Fase 7** (bersama R-16/R-17): notifikasi owner, pesan
kategori kosong, judul pricelist, fallback `cabang kami`, sisa komentar internal, lalu laporan
akhir `grep -i cabang`.

**Bukti**: `php artisan test --compact` → **271 passed, 7 skipped, 717 assertions**
(2 deprecation lingkungan: `PDO::MYSQL_ATTR_SSL_CA` di `config/database.php:62` +
`ReflectionMethod::setAccessible` di vendor collision, PHP 8.5).
`./vendor/bin/pint --test` pada 3 file yang disentuh → **PASS**.
Baseline Fase 0 (243 passed, tanpa test karakterisasi) → **271**:
Fase 0 +7, Fase 1 +14, Fase 2 +7.

## Fase 3 — Harga per kota & pemisahan produk/kota (R-01, R-06, R-15)

**Akar masalah**: harga hanya ada di `product_variants.price` (tanpa dimensi kota), dan
"produk global" hanya efek samping `region_id NULL` + `orWhereNull('region_id')` di 13 query.

**Perubahan**

- Migrasi `2026_11_14_000001_add_is_global_to_products_table.php`: kolom
  `products.is_global` (default false, index) + backfill `region_id NULL → is_global = true`
  supaya tidak ada produk yang hilang dari katalog. Reversible.
- Migrasi `2026_11_14_000002_create_product_variant_prices_table.php`: tabel override
  `product_variant_prices(product_variant_id, region_id, price)` + unique
  `(product_variant_id, region_id)`, FK cascade. Reversible.
- `app/Models/ProductVariantPrice.php` (baru) + relasi `ProductVariant::prices()` dan
  `priceForRegion()`; `Product`: `is_global` (fillable/cast) + scope `visibleInRegion()`
  (`is_global = true OR region_id = kota`).
- `app/Services/Pricing/PriceResolver.php` (baru): `priceFor()` fallback ke harga dasar,
  `cheapestVariant()` menghitung termurah dari harga kota. Di-inject ke `OrderBotService`.
- Semua `orWhereNull('region_id')` pada query produk diganti `visibleInRegion()`:
  13 titik di `OrderBotService`, `ProductController::index`, dan route `produk.json`.
- Harga per kota dipakai di semua titik baca: katalog penuh, katalog ringkas, pricelist,
  detail produk, pilihan varian, pencarian produk, keranjang/ringkasan, dan order.
  `produk.json` (form pesanan admin) juga mengembalikan harga kota.
- `ProductSeeder` menandai produk resmi `is_global = true`.
- `bot:audit-data`: laporan dipisah "produk global" vs "produk tanpa kota & tanpa is_global"
  (yang terakhir tidak tampil di kota mana pun).
- Test baru: `tests/Feature/Pricing/PriceResolverTest.php` (4),
  `tests/Feature/WhatsApp/OrderBotRegionPriceTest.php` (4) — override per kota, fallback,
  katalog terpisah per kota, harga varian, dan total order memakai harga kota.

**Catatan data**: semua produk `region_id NULL` yang ada sekarang otomatis menjadi global
(jawaban default untuk pertanyaan D4). Bila ada yang seharusnya khusus satu kota, isi
`region_id` produk itu; override harga diisi lewat `product_variant_prices` (Fase 10 / CSV).

**Bukti**: `php artisan test --compact` → **279 passed, 7 skipped, 739 assertions**
(2 deprecation lingkungan yang sama). `./vendor/bin/pint --test` pada 14 file yang
disentuh → **PASS**. Fase 3 menambah 8 test dari baseline Fase 2 (271).
