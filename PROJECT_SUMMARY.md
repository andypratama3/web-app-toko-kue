# Project Summary: Web App Toko Kue Pandan Asli

## 1. Ringkasan Umum

Aplikasi web **order management dan delivery tracking** untuk bisnis "Kue Pandan Asli" — bisnis kue tradisional Indonesia dengan rasa pandan alami. Aplikasi ini mendigitalisasi alur bisnis antara tiga role: **Admin**, **Kurir**, dan **Customer/Reseller**.

Bisnis beroperasi di **3 regional branch**: Surabaya, Malang, dan Denpasar (Bali).

---

## 2. Tech Stack

| Layer | Technology |
|---|---|
| Backend | Laravel 10, PHP 8.1+ |
| Frontend | Tailwind CSS 3, Alpine.js, jQuery, Livewire 3 |
| Auth | Laravel Jetstream (Fortify), Laravel Sanctum |
| RBAC | Spatie Laravel Permission v6 (`admin`, `kurir`) |
| PDF | barryvdh/laravel-dompdf v3 |
| Image | Intervention Image v3 |
| Build | Vite 6 |
| Database | MySQL/MariaDB |
| UI Template | Argon Dashboard (Tailwind-based) |
| Analytics | Google Tag Manager (GTM-PRXFHTTN), Google Analytics (G-6GHRM0X2ZS) |
| Chatbot | Meta/WhatsApp Business API (WebhookController) |

---

## 3. Struktur Database (Semua Tabel)

### `users`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| name | string | Auto Title Case |
| email | string unique | |
| password | string | |
| region_id | FK -> regions | nullable |
| note | text | nullable |
| timestamps | | |

### `regions`
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| name | string unique |
| slug | string unique |

Seed: **Surabaya**, **Malang**, **Denpasar**

### `categories`
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| name | string |
| slug | string unique |

### `customer_categories`
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| name | string |

Seed: **Reseller**, **Supermarket**

### `products`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| category_id | FK -> categories | |
| region_id | FK -> regions | nullable |
| name | string | |
| description | text | |
| image_path | string | nullable |
| tag | string | nullable |
| is_active | boolean | default true |

### `product_variants`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| product_id | FK -> products | cascade delete |
| name | string | e.g. "Isi 3 Pcs", "Isi 5 Pcs" |
| price | integer | |
| is_active | boolean | default true |

### `customers`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| region_id | FK -> regions | cascade |
| customer_category_id | FK -> customer_categories | nullable, set null |
| added_by_user_id | FK -> users | nullable, set null (kurir pemilik) |
| name | string | Auto Title Case |
| company_name | string | nullable, Auto Title Case |
| address | text | Auto Title Case |
| landmark | string | nullable |
| phone | string | Normalisasi format 62 |
| opening_hours | string | nullable |
| payment_type | string | nullable |
| note | text | nullable |
| is_flagged | boolean | default false |

### `orders`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| invoice_number | string | unique, nullable |
| customer_id | FK -> customers | cascade |
| phone | string | nullable |
| address | string | nullable |
| total_amount | decimal(10,2) | default 0 |
| payment_method | string | |
| payment_proof | string | nullable |
| note | text | nullable |
| rejection_note | text | nullable |
| created_by_user_id | FK -> users | nullable (kurir pembuat) |
| region_id | FK -> regions | nullable |
| status | string | default 'baru' |
| paid_at | timestamp | nullable |
| picked_up_at | timestamp | nullable |
| delivered_at | timestamp | nullable |
| received_by_buyer_at | timestamp | nullable |

**Status order**: `baru` -> `dikemas` -> `diambil` -> `diantar` -> `diterima_pembeli` -> `selesai` -> `diverifikasi_admin`

**Branch lain**: `menunggu_retur` -> `menunggu_verifikasi_admin` -> `diverifikasi_admin`, atau `dibatalkan`

### `order_items`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_id | FK -> orders | cascade |
| product_id | FK -> products | |
| product_name | string | snapshot nama produk |
| variant_id | FK -> product_variants | nullable |
| variant_name | string | nullable |
| quantity | integer | |
| price | decimal(10,2) | |
| subtotal | decimal(10,2) | |

### `order_returns`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_id | FK -> orders | cascade |
| courier_id | FK -> users | |
| region_id | FK -> regions | |
| status | string | default 'menunggu_konfirmasi' |
| total_amount_returned | decimal(15,2) | default 0 |
| return_proof | string | nullable |
| reason | text | nullable |
| admin_notes | text | nullable |

**Status return**: `menunggu_konfirmasi` -> `menunggu_verifikasi_admin` -> `disetujui`/`ditolak` -> `selesai`

### `order_return_products`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint PK | |
| order_return_id | FK -> order_returns | cascade |
| product_id | FK -> products | cascade |
| product_variant_id | FK -> product_variants | nullable, cascade |
| quantity | integer | |
| price | decimal(15,2) | |
| subtotal | decimal(15,2) | |

### `visit_logs`
| Kolom | Tipe |
|---|---|
| id | bigint PK |
| ip_address | string(45), indexed |

### Tabel Spatie Permission
`permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`

### Tabel Standard Laravel
`sessions`, `password_reset_tokens`, `failed_jobs`, `personal_access_tokens`

---

## 4. Model & Relasi

### User
- `belongsTo(Region)`
- `hasMany(Customer, 'added_by_user_id')` — kurir punya banyak customer
- Menggunakan trait `HasRoles` (Spatie)

### Region
- `hasMany(User)`

### Category
- `hasMany(Product)`

### Product
- `belongsTo(Category)`, `belongsTo(Region)`
- `hasMany(ProductVariant)`

### ProductVariant
- `belongsTo(Product)`
- `hasMany(OrderItem, 'variant_id')`

### Customer
- `belongsTo(Region)`, `belongsTo(CustomerCategory)`
- `belongsTo(User, 'added_by_user_id')` — kurir yang menambah

### Order
- `hasMany(OrderItem)` — item dalam pesanan
- `belongsTo(Customer)`, `belongsTo(User, 'created_by_user_id')`, `belongsTo(Region)`
- `hasMany(OrderReturn)` — bisa ada beberapa retur

### OrderItem
- `belongsTo(Order)`, `belongsTo(Product)`, `belongsTo(ProductVariant)`

### OrderReturn
- `hasMany(OrderReturnProduct)`
- `belongsTo(Order)`, `belongsTo(User, 'courier_id')`, `belongsTo(Region)`

### OrderReturnProduct
- `belongsTo(OrderReturn)`, `belongsTo(Product)`, `belongsTo(ProductVariant)`

---

## 5. Routes

### Public (web.php)
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/` | Landing page (Livewire Homepage, tracking visitor IP) |
| GET | `/privacy-policy` | Halaman kebijakan privasi |
| POST | `/logout` | Logout handler |

### Auth Redirect
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/dashboard` | Auto-redirect ke admin/kurir dashboard sesuai role + region |

### Admin Routes (prefix: `/admin`, middleware: `role:admin`)

| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/dashboard/{region}` | Dashboard admin + charts + visitor stats |
| GET | `/admin/profile` | Profil admin |
| PUT | `/admin/profile` | Update profil |
| PUT | `/admin/profile/password` | Update password |

**Orders:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/orders` | List order menunggu verifikasi |
| GET | `/admin/orders/{id}/details` | JSON detail order (modal) |
| POST | `/admin/orders/{id}/verify` | Verifikasi/approve order |
| POST | `/admin/orders/{id}/reject` | Tolak order |
| DELETE | `/admin/orders/{id}` | Hapus order permanen |

**Couriers:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/couriers` | List kurir + AJAX search |
| POST | `/admin/couriers` | Buat kurir baru |
| PUT | `/admin/couriers/{courier}` | Update kurir |
| DELETE | `/admin/couriers/{courier}` | Hapus kurir |
| PUT | `/admin/couriers/{courier}/note` | Update catatan kurir |
| GET | `/admin/couriers/{courier}/performance-data` | JSON data performa kurir |

**Performance:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/peforma-kurir` | Ranking performa kurir (paginated) |
| GET | `/admin/peforma-kurir/{kurir}` | Detail performa kurir |
| GET | `/admin/peforma-kurir/export/pdf` | Export performa kurir ke PDF |
| GET | `/admin/peforma-kurir/export/{id}/pdf` | Export performa kurir tertentu |
| GET | `/admin/peforma-customer` | Ranking performa customer (bulanan) |
| GET | `/admin/peforma-customer/{customer}` | Detail performa customer |
| GET | `/admin/peforma-customer/export/pdf` | Export performa customer ke PDF |

**Products:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/products` | List produk per kategori |
| POST | `/admin/products` | Buat produk + image + variants |
| GET | `/admin/products/{product}` | Detail produk |
| PUT | `/admin/products/{product}` | Update produk + variants |
| DELETE | `/admin/products/{product}` | Hapus produk + gambar |

**Customers:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/customers` | List customer |
| POST | `/admin/customers` | Buat customer |
| PUT | `/admin/customers/{customer}` | Update customer |
| DELETE | `/admin/customers/{customer}` | Hapus customer |
| PUT | `/admin/customers/{customer}/note` | Update catatan customer |
| POST | `/admin/customers/{customer}/flag` | Toggle flag customer |
| GET | `/admin/customers/{customer}/rekap/download` | Download PDF rekap order customer |

**History:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/admin/historys` | List history order terverifikasi + filter |
| GET | `/admin/historys/{order}/details` | JSON detail history |
| GET | `/admin/historys/{order}/invoice` | Halaman invoice |
| GET | `/admin/historys/{order}/download` | Download invoice PDF |
| GET | `/admin/historys/export-pdf` | Export history bulanan ke PDF |
| DELETE | `/admin/historys/{id}` | Hapus history |

### Kurir Routes (prefix: `/kurir`, middleware: `role:kurir`)

| Method | URI | Deskripsi |
|---|---|---|
| GET | `/kurir/dashboard/{region}` | Dashboard kurir |
| GET | `/kurir/profile` | Profil kurir |
| PUT | `/kurir/profile` | Update profil |
| PUT | `/kurir/profile/password` | Update password |

**Pesanan:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/kurir/pesanan` | List pesanan aktif + filter status |
| GET | `/kurir/pesanan/create` | Form buat pesanan baru |
| POST | `/kurir/orders/checkout` | Buat pesanan (AJAX) |
| GET | `/kurir/pesanan/{id}/details` | JSON detail pesanan |
| POST | `/kurir/pesanan/{id}/update-status` | Update status pengiriman |
| POST | `/kurir/pesanan/{id}/upload-proof` | Upload bukti bayar (compressed JPEG) |
| GET | `/kurir/customer/{id}/last-order` | Ambil order terakhir customer (re-order) |

**Retur:**
| Method | URI | Deskripsi |
|---|---|---|
| POST | `/kurir/pesanan/{order}/request-return` | Ajukan retur |
| POST | `/kurir/pesanan/{order}/upload-return-proof` | Upload bukti retur |
| POST | `/kurir/pesanan/{order}/request-return/edit` | Edit retur pending |

**Customers & History:**
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/kurir/customers` | List customer milik kurir |
| POST | `/kurir/customers` | Buat customer |
| PUT | `/kurir/customers/{customer}` | Update customer |
| DELETE | `/kurir/customers/{customer}` | Hapus customer |
| PUT | `/kurir/customers/{customer}/note` | Update catatan |
| GET | `/kurir/historys` | List history order kurir |
| GET | `/kurir/historys/{order}/details` | Detail history |
| GET | `/kurir/historys/{order}/invoice` | Invoice |
| GET | `/kurir/historys/{order}/download` | Download invoice PDF |
| GET | `/kurir/historys/export-pdf` | Export history bulanan |
| DELETE | `/kurir/historys/{id}` | Hapus history |

### API Routes (api.php)
| Method | URI | Deskripsi |
|---|---|---|
| GET | `/api/user` | Ambil user terotentikasi (Sanctum) |
| GET | `/api/webhook/meta` | Verifikasi webhook Meta/WhatsApp |
| POST | `/api/webhook/meta` | Terima pesan WhatsApp |

---

## 6. Livewire Components

### `App\Livewire\Homepage`
- **Path**: `app/Livewire/Homepage.php`
- **View**: `resources/views/livewire/homepage.blade.php`
- **Fungsi**: Landing page publik dengan hero section, about us, galeri produk, testimoni, lokasi, dan footer
- **Properties**: `isMobileMenuOpen` (toggle mobile menu)

---

## 7. Fitur Utama

### Alur Pesanan (State Machine)
```
baru -> dikemas -> diambil -> diantar -> diterima_pembeli -> selesai -> diverifikasi_admin
                                         \-> menunggu_retur -> menunggu_verifikasi_admin -> diverifikasi_admin
                                        \-> dibatalkan
```

### Multi-Region
- 3 regional: Surabaya (WIB), Malang (WIB), Denpasar/Bali (WITA)
- Timezone-aware timestamps (`Asia/Jakarta` / `Asia/Makassar`)
- Semua data (produk, customer, order) di-scope per region

### Role-Based Access Control (RBAC)
- **Admin**: Dashboard charts, manage produk/customer/kurir, verifikasi order, performa, export PDF
- **Kurir**: Buat order, update status pengiriman, upload bukti bayar, manage customer sendiri, ajukan retur

### Customer Category System
- **Reseller**: Maks 7 order aktif
- **Supermarket**: Maks 30 order aktif
- Skor performa: 70% volume pembelian + 30% anti-retur

### Sistem Produk & Variant
- Produk punya beberapa variant (e.g. "Isi 3 Pcs", "Isi 5 Pcs") dengan harga berbeda
- Variant bisa di-nonaktifkan (soft delete) jika sudah terkait order

### Sistem Retur/Refund
- Kurir bisa ajukan retur untuk order status "diterima_pembeli"
- Retur include detail produk yang dikembalikan + jumlah
- Bukti retur bisa di-upload sebagai gambar
- Admin bisa setujui atau tolak retur

### Invoice & PDF
- Invoice individual per order (DomPDF)
- Export history bulanan ke PDF
- Rekap order customer per rentang tanggal (PDF)
- Export performa kurir/customer ke PDF

### Performa Analytics
- **Kurir**: Ranking berdasarkan jumlah order, total revenue, jumlah customer + filter tanggal
- **Customer**: Algoritma scoring (70% pembelian + 30% anti-retur), ranking bulanan
- **Dashboard**: Chart penjualan harian/mingguan/bulanan + tracking visitor (visit_logs by IP)

### Image Processing
- Bukkti bayar di-compress ke JPEG 60% quality (Intervention Image) sebelum disimpan
- Gambar produk via `Storage::disk('public')`

### Format Nomor Invoice
```
INV/{DDMM}/{RegionID 2 digit}/{CourierID 3 digit}/{CustomerID 3 digit}/{DailySequence 3 digit}
Contoh: INV/0809/01/004/012/001
```

### WhatsApp Chatbot
- Integrasi Meta/WhatsApp Business API via webhook
- Mendukung teks, gambar, lokasi, carousel, dan button interactive messages

### Dark Mode
- Full dark mode via Tailwind CSS `class` strategy
- Toggle di-persist di localStorage
- FOUC prevention script di layout head

### AJAX Live Search
- Customer, order, history, dan kurir mendukung AJAX live search + pagination
- Return JSON dengan rendered HTML partials (desktop + mobile)

---

## 8. Patterns & Konfigurasi Penting

### Data Ownership Enforcement
- Kurir hanya bisa lihat/modif customer yang dibuatnya (`added_by_user_id`)
- Admin hanya bisa akses data dalam region-nya (`region_id`)
- Semua controller mengecek ownership sebelum operasi, return 403 jika melanggar

### Auto Title-Case Mutators
Model `User` dan `Customer` auto-format `name`, `address`, `company_name` ke Title Case via Eloquent Attribute mutators

### Phone Number Normalization
`CustomerController::formatPhoneNumber()` mengkonversi semua nomor telepon ke format prefix `62` (format internasional Indonesia)

### File Storage Pattern
```
Produk       -> storage/app/public/products/
Bukti Bayar  -> storage/app/public/payment_proofs/
Bukti Retur  -> storage/app/public/return_proofs/
```

### Seed Data
- 2 role: `admin`, `kurir`
- 3 region: Surabaya, Malang, Denpasar
- 3 admin (1 per region), 3 kurir (1 per region)
- Default password: `password`
- 2 customer category: `Reseller`, `Supermarket`

### Tailwind Safelist
`tailwind.config.js` menyertakan safelist komprehensif untuk mencegah PurgeCSS menghapus dynamic class dari Argon Dashboard sidebar, badges, dan responsive patterns

### Notification Badge
Sidebar nav menampilkan badge merah dengan jumlah order baru (status `pending`) untuk admin, dan jumlah order ditolak untuk kurir — memberikan awareness operasional real-time

---

## 9. WhatsApp Business API Chatbot (Multi-Cabang)

### Arsitektur
```
Meta Cloud API → Webhook → WhatsAppWebhookController (thin) → ProcessWhatsAppWebhookJob (async)
                                                                      ↓
                                                             OrderBotService (state machine)
                                                                      ↓
                                              Orders + OrderItems (tabel existing) ← Integrasi langsung
```

### Tabel Baru (Additive, tidak mengubah tabel existing)
| Tabel | Fungsi |
|---|---|
| `whatsapp_conversations` | Percakapan per nomor WA (UUID, state, context JSON) |
| `whatsapp_messages` | Log semua pesan masuk/keluar (dedup by whatsapp_message_id) |
| `whatsapp_message_statuses` | Status pengiriman pesan (sent/delivered/read/failed) |
| `delivery_zones` | Lookup zona ongkir per region (dikelola admin) |

### Kolom Baru pada Tabel Existing
- `orders.channel` — menandai order berasal dari `whatsapp` vs `web`/`kurir`
- `regions.meta_phone_number_id` — binding nomor WhatsApp per cabang (multi-cabang)

### State Machine Bot (`OrderBotConversationState`)
```
INIT → WELCOME_SENT → MENU_SELECTION → PRODUCT_BROWSING → AWAITING_ORDER_FORM
  → AWAITING_DELIVERY_METHOD → AWAITING_LOCATION_OR_ADDRESS → AWAITING_DELIVERY_SLOT
  → ORDER_SUMMARY → AWAITING_PAYMENT_PROOF → ORDER_CONFIRMED → CLOSED
```

### Service Layer
| Service | Fungsi |
|---|---|
| `WhatsappMetaService` | Kirim teks/list/button/template, download media |
| `OrderBotService` | State machine alur order, resolve produk dari DB |
| `DeliveryZoneService` | Hitung ongkir berbasis jarak (Haversine), tier: <10km=10rb, 10-14km=15rb, >14km=eskalsi |
| `IncomingMediaHandler` | Download + kompres gambar bukti TF (Intervention Image 60%) |
| `AdminNotificationRouterService` | Notifikasi ke admin/kurir sesuai region |

### Webhook Endpoints
```
GET  /api/webhook/meta          → Verifikasi Meta (backward compatible)
POST /api/webhook/meta          → Handle pesan (async via Job)
GET  /api/v1/webhook/whatsapp   → Verifikasi Meta (versioned)
POST /api/v1/webhook/whatsapp   → Handle pesan (async via Job)
```

### Business Rules Bot
- **Ongkir**: <10km = Rp10.000; 10-14km = Rp15.000; >14km = eskalasi manusia
- **Grab/GoSend**: ditanggung langsung customer ke driver, tidak masuk `total_amount`
- **Slot waktu**: 09-11, 11-13, 13-15, 15-17 (timezone per region)
- **Kuota order**: Reseller max 7, Supermarket max 30 — dihormati bot
- **Verifikasi**: Pembayaran & order tidak otomatis lunas — tetap perlu admin

### Config (`.env`)
```
META_VERIFY_TOKEN=
META_PHONE_NUMBER_ID=
META_ACCESS_TOKEN=
META_WEBHOOK_SECRET=
META_VERSION=v21.0
META_BOT_NAME="Kue Pandan Asli"
META_DEFAULT_REGION=Denpasar
```

### Arsitektur Async
- Webhook POST hanya validasi signature + dispatch job → respond `200 OK` dalam <5 detik
- Semua logic bot dijalankan di `ProcessWhatsAppWebhookJob` (queue: database/redis)
- Dedup pesan berdasarkan `whatsapp_message_id` unik
- Signature verification via `X-Hub-Signature-256` HMAC

### Multi-Cabang
- **1 nomor WA per cabang** (data-driven): kolom `regions.meta_phone_number_id`
- Webhook membaca `metadata.phone_number_id` dari payload → percakapan baru otomatis dapat `region_id` cabang pemilik nomor tsb; nomor tak terdaftar di-*tolak* (403)
- Semua kirim keluar (bot, reply admin, notif status) memakai nomor cabang percakapan; fallback ke `META_PHONE_NUMBER_ID` & `META_DEFAULT_REGION` bila tak ada binding
- Semua data produk/harga dari DB, di-scope per `region_id`
- Delivery zones per region, dikelola admin via CRUD
- Channel `whatsapp` pada orders membedakan origin order

### Open Questions (Perlu Konfirmasi Owner)
1. ✅ Terjawab: multi-cabang pakai daftar nomor per region (`regions.meta_phone_number_id`) — isi lewat `UPDATE regions ...`
2. `created_by_user_id` untuk order via bot: user "system" atau admin cabang?
3. Status order baru khusus WA atau cukup pakai `baru` + kolom `channel`?
4. Kontak eskalasi manusia untuk jarak >14km per region (saat ini hanya teks cabang)
