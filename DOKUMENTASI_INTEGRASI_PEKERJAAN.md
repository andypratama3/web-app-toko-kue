# 📦 Dokumentasi Integrasi & Pekerjaan — Web App Toko Kue Pandan Asli

> Aplikasi order management & delivery tracking untuk **Kue Pandan Asli** (kue tradisional rasa pandan alami).
> Role: **Admin**, **Kurir**, **Customer/Reseller** — beroperasi di 3 cabang: **Surabaya, Malang, Denpasar**.
> Branch aktif: `Main-V-1-0-0` · Hosting: **Cloud Hosting** (shared hosting, tanpa Node/VPS).

---

## 1. Tech Stack & Dependensi

| Layer | Teknologi |
|---|---|
| Backend | Laravel 10, PHP 8.1+ |
| Frontend | Tailwind CSS 3 (`darkMode: class`), Alpine.js, Livewire 3, jQuery |
| Auth | Laravel Jetstream 4 + Fortify + Sanctum |
| RBAC | Spatie Laravel Permission 6 (`admin`, `kurir`) |
| PDF | barryvdh/laravel-dompdf 3 |
| Build | Vite 6 (build lokal, artifact di-commit karena shared hosting) |
| DB | MySQL/MariaDB |
| UI Template | Argon Dashboard 2 (Tailwind) + halaman landing kustom |
| HTTP | Guzzle 7 |
| Analytics | Google Tag Manager (`GTM-PRXFHTTN`), Google Analytics (`G-6GHRM0X2ZS`) |

**Composer require:** `barryvdh/laravel-dompdf`, `guzzlehttp/guzzle`, `laravel/fortify`, `laravel/framework`, `laravel/jetstream`, `laravel/sanctum`, `laravel/tinker`, `spatie/laravel-permission`.

---

## 2. Daftar Integrasi Eksternal

### 2.1 🔵 Meta/WhatsApp Business API (INTEGRASI UTAMA)
Menghubungkan nomor WhatsApp bisnis (per cabang) ke aplikasi. Bertenaga **Graph API v24.0**.

**Konfigurasi (`.env`):**
```
META_PHONE_NUMBER_ID=1280849428445565
META_ACCESS_TOKEN=EAAP…
META_VERIFY_TOKEN=          # isi token bebas, wajib sama dengan di dashboard Meta
META_WEBHOOK_SECRET=        # isi secret untuk validasi tanda tangan request
META_VERSION=v24.0
META_BOT_NAME="Kue Pandan Asli"
META_DEFAULT_REGION=Malang
```
> Izin token saat ini: **messaging** saja (tanpa `whatsapp_business_management`). Cukup untuk kirim/terima pesan, list message, media.

**Endpoint Webhook** — 3 alias semi-publik (semua → `WhatsAppWebhookController`):
| Method | URL |
|---|---|
| GET (verify) | `https://kuepandanasli.com/api/webhook/whatsapp/meta` ⭐ (terdaftar di Meta) |
| POST (event) | `https://kuepandanasli.com/api/webhook/whatsapp/meta` |
| GET/POST | `https://kuepandanasli.com/api/webhook/meta` (backward compat) |
| GET/POST | `https://kuepandanasli.com/api/v1/webhook/whatsapp` |

- **Hubungkan (GET)** — memverifikasi `hub.mode`, `hub.verify_token` (vs `META_VERIFY_TOKEN`).
- **Terima event (POST)** — validasi **X-Hub-Signature-256** (SHA-256 HMAC) bila `META_WEBHOOK_SECRET` diisi; menolak tanda tangan salah.
- Multi-cabang: event dibaca dari `metadata.phone_number_id`; nomor tak dikenal → **403**.

### 2.2 🧭 Multi-Cabang (Satu Nomor WA per Cabang)
Setiap region (cabang) bisa punya **nomor WA / `phone_number_id` sendiri**.
- Kolom baru: `regions.meta_phone_number_id` (nullable, additive).
- `Region::findByPhoneNumberId()` — resolve percakapan ke cabang dari nomor pengirim.
- `WhatsappMetaService` **auto-derive** nomor keluar (outbound) dari region percakapan, fallback ke `META_PHONE_NUMBER_ID`; semua method kirim/mark-as-read/typing menerima optional `?string $phoneNumberId` untuk override.
- Controller webhook: `metadata.phone_number_id` → 403 jika tidak dikenal.

### 2.3 🤖 Chatbot WhatsApp (OrderBotService)
State machine order lengkap di WhatsApp. Alur utama:
1. `INIT` → sapaan + info cabang.
2. `WELCOME_SENT` → ketik `PESAN` (pilih kategori) atau `PRODUK` (katalog).
3. `MENU_SELECTION` → list kategori (list message Meta).
4. `PRODUCT_BROWSING` → cari produk by keyword, `pesan`/`menu`.
5. `AWAITING_ORDER_FORM` → form order (produk → penerima → alamat → tanggal → jam).
6. `AWAITING_DELIVERY_METHOD` → 1=Ambil Sendiri · 2=Grab/GoSend · 3=Kurir Internal.
7. `AWAITING_LOCATION_OR_ADDRESS` → hitung ongkir via DeliveryZoneService (kanal lokasi/kirim alamat).
8. `AWAITING_DELIVERY_SLOT` → slot waktu pengiriman.
9. `ORDER_SUMMARY` → ringkasan + total → `FIX`/`BATAL`.
10. `AWAITING_PAYMENT_PROOF` → terima **gambar bukti bayar** (QRIS), `SKIP`/`lewati`/`nanti` juga boleh.
11. `ORDER_CONFIRMED` → notifikasi admin otomatis → `CLOSED`.

Beberapa caption tombol (interactive list) di-normalisasi: `cat_tumpeng`→`tumpeng`, `delivery_2`→`2`, `slot_am`→`AM`, dst.

### 2.4 🗓️ Webhook `archived` & Status Pengiriman
- Event `messages[].status` (sent/delivered/read) disimpan ke `whatsapp_message_statuses`.
- Event `changed_field = 'messages'` + tag `archived` → percakapan diarsipkan di admin chat.

### 2.5 👨💼 Chat Monitor & Notifikasi Admin
- **Admin Chat Monitor** (`/admin/chat`) — daftar percakapan, buka percakapan, balas manual (admin ↔ customer 1:1), lihat status pesan.
- **Admin Notifications** (`admin_notifications`) — notifikasi order baru & status penting ke admin cabang saat `orders` dibuat/diubah via web.
- **AdminNotificationRouterService** — routing notif ke admin cabang terkait.

### 2.6 📦 Ongkir & Delivery Zone
- Tabel `delivery_zones` (per `region_id`): harga ongkir per jarak.
- `DeliveryZoneService::estimateDistance()` + `calculateOngkir()` — estimasi jarak (Haversine) & ongkir; >14 km → eskalasi ke admin manusia (state `ESCALATED_TO_HUMAN`).

### 2.7 🖼️ Media (Bukti Bayar / Bukti Retur)
- `IncomingMediaHandler` — unduh media via Graph API (`downloadMedia`), kompres (`compressImage`, kualitas 60%), simpan ke storage `whatsapp-media/payment/…` & `return/…` (Laravel Storage → `storage/app/public` + `php artisan storage:link`).
- `handlePaymentProof` **reuse** `media_path` (tanpa unduh ulang bila path sudah ada di WhatsAppMessage).

### 2.8 📄 Lainnya
| Integrasi | Keterangan |
|---|---|
| PDF | Invoice, rekap order, export peforma kurir/customer (dompdf) |
| Google Analytics/GTM | Landpage publik + pelacakan |
| Auth/E-mail | Laravel Jetstream (login, 2FA, profile) |

---

## 3. Riwayat Pekerjaan yang Telah Dilakukan

### 3.1 ✨ Feature: WhatsApp Chatbot Multi-Cabang + Admin Chat Monitor & Notifikasi
**Commit `6128324`** (54 file, +5276/−271) → `origin/Main-V-1-0-0`.
- Migration `regions.meta_phone_number_id` + model `Region::findByPhoneNumberId()`.
- `WhatsappMetaService` refactor: per-region outbound, override param.
- `WhatsAppWebhookController` — verify/signature/403 unknown source.
- `ProcessWhatsAppWebhookJob` — resolve region baru via nomor, markAsRead override.
- State machine `OrderBotService` (13 state), katalog, form order, slot, ongkir, bukti bayar.
- `IncomingMediaHandler` — unduh + kompres bukti bayar/retur.
- `AdminNotificationRouterService` + tabel `admin_notifications`.
- Livewire **Admin Chat Monitor** + balasan manual + status pesan.
- `.env.example` & `PROJECT_SUMMARY.md` diperbarui.
- **Tests:** `MultiBranchWebhookTest.php` (5 test).

### 3.2 🐛 Audit & Perbaikan OrderBotService
- **`notifyNewOrder` dipindah ke `confirmOrder`** — notifikasi admin selalu terkirim meski bukti bayar dilewati (`SKIP`). + test penjaga.
- **`handlePaymentProof` reuse media_path** — tidak ada unduhan ganda bila path sudah tersimpan.
- **`updateConversationProfile` menormalisasi nomor** via `Phone::normalize` (format `62…`).

### 3.3 💡 Perbaikan Asset & JS (Dark Mode — batch 1, commit `9b3b847`)
- Hapus CDN Tailwind (403 + warning prod) → Tailwind lokal.
- Hapus CDN Alpine dobel (Livewire sudah bundling Alpine) di `argon.blade.php` & `homepage.blade.php`.
- Hapus FontAwesome kit 403.
- `app.js`: urutan import Chart.js dibenahi (fix `Chart is not defined`).
- `argon-dashboard-tailwind.js`: loader dinamis (perfect-scrollbar, navbar-sticky, sidenav-burger, dst) dinonaktifkan — sumber 404.
- Hapus `dark-mode-toggle.js` mati; entry Vite dihapus.
- Tema disatukan ke `localStorage['theme']` (baca legacy `color-theme`), applied pre-paint di `<html>`.

### 3.4 🎨 Overhaul Dark Mode UI (batch 2, commit `9b3b847`)
- **Akar bug "teks hitam di dark mode":** `dark:text-black` dan `dark:text-bluedark` (#111c43) → diganti `dark:text-white` (judul Resume Hari Ini + **judul semua halaman** di wrapper `content.blade.php`).
- `dark:bg-slate-850` (warna custom ✗ tidak ada di config) → **`dark:bg-slate-800`** (19×) — sidebar & form semula tetap putih di dark mode.
- `dark:shadow-dark-xl` (custom ✗) → `dark:shadow-xl`.
- Input/select/textarea `bg-gray-50`+`text-gray-900` → tambah `dark:bg-gray-600/slate-800 dark:text-gray-100 dark:placeholder-gray-400` (customers create/edit, products create/edit, pesanan create, note modals, dll.).
- Tombol Batal modal: `dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600` ratio konsisten.
- Empty-state tabel, harga coret (`line-through`), tab filter, timestamp chat, tabel default → `dark:text-slate-300/400`.
- Navbar dropdown profile & toggle knob, sidenav toggle, body layout `dark:text-slate-300`, component `modal/dialog-modal/secondary-button/content-wrapper`.
- **Verified build:** `public/build/assets/app-vO9Nz_dC.css` memuat `dark:bg-slate-800`, `dark:text-white`, `dark:bg-gray-700`, dst; `slate-850` = 0.

### 3.5 🔧 Perbaikan Deployment & Infra
- **Webhook endpoint 3 alias** + dokumentasi callback URL resmi.
- **Fix migrasi server:** DB hasil dump → tabel `migrations` kosong → `php artisan migrate` gagal di `visit_logs`. Solusi: jalankan script pencatat migration sebelum tanggal `2026_09_08` ke tabel `migrations` (tanpa memindah/menghapus file agar test `RefreshDatabase` tetap jalan), lalu lanjut migrasi baru.
- **Peringatan seeder:**
  - `RoleAndUserSeeder` — buat user dummy (**tidak idempotent**).
  - `ProductSeeder` — **TRUNCATE** `categories`/`products`/`product_variants` → **JANGAN di production**.
  - `CustomerCategorySeeder` — tidak idempotent.
  - `DeliveryZoneSeeder` — **aman**, jalankan sekali: `php artisan db:seed --class=DeliveryZoneSeeder`.

---

## 4. Database (Tabel WhatsApp/Order terkait)

| Tabel | Keterangan |
|---|---|
| `regions` | `+ meta_phone_number_id` (nullable) |
| `whatsapp_conversations` | Percakapan per nomor, `region_id`, `current_state` (enum bot), `context` (JSON form/order), customer_link |
| `whatsapp_messages` | Pesan masuk/keluar, `is_from_bot`, `media_path`, `payload` |
| `whatsapp_message_statuses` | Log status sent/delivered/read |
| `admin_notifications` | Notifikasi order/bot ke admin |
| `delivery_zones` | Ongkir per jarak per region |
| `orders` | `+ channel='whatsapp'`, region_id, invoice_number |
| `order_items`, `order_returns`, `order_return_products` | Detail order & retur |

**Migration WhatsApp (batch `2026_09_08…`):** conversations, messages, message_statuses, delivery_zones, channel di orders, jobs queue, admin_notifications, meta_phone_number_id di regions. Semua **additive** (nullable/default) — tanpa reset data.

---

## 5. Pengujian (Pest/PHPUnit)

```
Tests: 76 passed, 7 skipped (148 assertions)
```
Cakupan fitur WhatsApp: `WebhookVerificationTest`, `WebhookMessageProcessingTest`, `OrderBotStateTransitionTest`, `MultiBranchWebhookTest`, `AdminChatReplyTest`, `AdminNotificationsTest`, `ChatConversationActionsTest`, `CustomerQuotaTest`, `DeliveryZoneServiceTest` (unit). Sisanya: auth Jetstream standar.

---

## 6. Deploy ke Cloud Hosting (Shared Hosting — Tanpa Node/VPS)

> Server **tidak bisa** `npm run build` (tanpa node/npm). Build selalu dilakukan **lokal**, lalu **artifact `public/build/` ikut di-commit & push**.

```bash
# Lokal
npm install
npm run build                 # menghasilkan public/build/assets/* (hashed)
git add -A
git commit -m "fix/feat: …"
git push origin Main-V-1-0-0

# Server (pull saja)
git pull origin Main-V-1-0-0
php artisan migrate           # setelah fix migrasi base
php artisan db:seed --class=DeliveryZoneSeeder   # SEKALI saja
php artisan queue:work --daemon &                # proses job webhook
php artisan storage:link                         # akses media/bukti bayar
php artisan view:cache
```

**Checklist aktivasi WhatsApp di production:**
1. Isi `META_VERIFY_TOKEN` & `META_WEBHOOK_SECRET` di `.env`.
2. Daftarkan callback URL resmi `https://kuepandanasli.com/api/webhook/whatsapp/meta` di dashboard Meta.
3. Isi `regions.meta_phone_number_id` tiap cabang.
4. Jalankan queue worker (webhook & notifikasi async via job).

---

## 7. Catatan Terbuka / Pending

- **Bot: daftar kategori masih hardcode** (Tumpeng/Hampers/Ala Carte) di `sendProductCategories()` & `handleMenuSelection()`. Belum makin dinamis sesuai isi tabel `categories` — kandidat perubahan berikutnya agar "bot bisa menjangkau seluruh produk/kategori".

---

## 8. Struktur Kode WhatsApp

```
app/
├── Http/Controllers/Chatbot/
│   └── WhatsAppWebhookController.php      # verify + handle event (signature, 403 source)
├── Jobs/
│   └── ProcessWhatsAppWebhookJob.php      # async: resolve region, simpan msg, route ke bot
├── Enums/
│   └── OrderBotConversationState.php      # 14 state
├── Models/
│   ├── WhatsAppConversation / Message / MessageStatus
│   ├── AdminNotification
│   └── Region (findByPhoneNumberId)
└── Services/WhatsApp/
    ├── WhatsappMetaService.php            # Graph API: send text/list/buttons, media, typing, markAsRead (per-region)
    ├── OrderBotService.php                # state machine + message builder + confirmOrder
    ├── AdminNotificationRouterService.php # notif admin cabang
    ├── DeliveryZoneService.php            # ongkir per jarak
    └── IncomingMediaHandler.php           # unduh + kompres bukti bayar/retur
```