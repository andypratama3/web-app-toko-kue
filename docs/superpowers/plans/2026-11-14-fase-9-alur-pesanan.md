# Fase 9 Alur Pesanan Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menyederhanakan percakapan pemesanan WhatsApp menjadi alur linear yang dapat keluar dari setiap state aktif.

**Architecture:** State enum dan service utama tetap dipertahankan. Perubahan difokuskan pada routing perintah, copy sambutan, footer terpusat, urutan field form, serta test perilaku yang memverifikasi alur dari pelanggan baru dan pelanggan yang pindah kota.

**Tech Stack:** Laravel 10, PHP 8.1+, Laravel HTTP client/queue, PHPUnit/Pest melalui `php artisan test`.

**Spec:** `docs/superpowers/specs/2026-11-14-fase-9-alur-pesanan-design.md`

## Global Constraints

- D1–D11 tetap berlaku tanpa perubahan.
- R-07 tetap OPEN dan tidak diimplementasikan.
- `.env` tidak dibaca atau diubah; kredensial baru hanya masuk `.env.example`.
- Teks pelanggan WhatsApp maksimal 4096 karakter.
- `ConversationRegionResolver` menentukan kota; `PriceResolver` menentukan harga.
- Tidak ada push, merge, atau deployment.

## Review Focus

- Pelanggan mengetik teks bebas pada state yang tidak mengharapkan input: bot harus memberi balasan singkat dan footer.
- Pelanggan berada di field form: MENU tidak boleh menelan isi field; GANTI KOTA dan BATAL tetap memiliki jalur yang jelas.
- Pelanggan pindah kota saat keranjang berisi: item yang tidak tersedia dibuang, item global tetap bertahan, dan checkout tetap dapat diteruskan.
- Pelanggan menekan FIX/SKIP berulang: tidak ada order ganda atau state pembayaran yang rusak.
- Footer tidak boleh menduplikasi perintah dalam badan pesan atau memunculkan perintah yang tidak didukung state.

---

### Task 1: Copy Sambutan, Footer, dan Config Admin

**Files:**
- Modify: `config/bot.php`
- Modify: `.env.example`
- Modify: `app/Services/WhatsApp/OrderBotService.php`
- Test: `tests/Feature/WhatsApp/OrderBotFlowSimplificationTest.php`

**Interfaces:**
- Produces: `OrderBotService::footerFor(OrderBotConversationState $state): string`
- Produces: `config('bot.admin_phone')`

- [ ] **Step 1: Write failing tests** untuk sambutan singkat, empat perintah utama, kontak admin terlihat hanya bila config terisi, dan footer setiap state.
- [ ] **Step 2: Run focused test** dan pastikan gagal karena footer/config belum ada.
- [ ] **Step 3: Implement config, `footerFor`, helper penambahan footer, dan copy welcome baru tanpa mengubah alur order.
- [ ] **Step 4: Run focused test** dan suite terkait; pastikan hijau.
- [ ] **Step 5: Commit** `feat(bot): footer terpusat dan sambutan ringkas`.

### Task 2: Satu Pintu Katalog, Kota Lebih Dulu, dan Jalan Keluar

**Files:**
- Modify: `app/Services/WhatsApp/OrderBotService.php`
- Modify: `app/Services/WhatsApp/ProductListRenderer.php`
- Test: `tests/Feature/WhatsApp/OrderBotFlowSimplificationTest.php`
- Update regressions: `tests/Feature/WhatsApp/OrderBotCharacterizationTest.php`, `tests/Feature/WhatsApp/OrderBotCitySwitchTest.php`, `tests/Feature/WhatsApp/OrderBotRobustnessTest.php`, `tests/Feature/WhatsApp/OrderBotStateTransitionTest.php`

**Interfaces:**
- Produces: `openProductCatalog(WhatsAppConversation $conversation): void`
- Produces: `openCategoryMenu(WhatsAppConversation $conversation): void`
- Produces: `openPricelist(WhatsAppConversation $conversation): void`

- [ ] **Step 1: Write failing tests** bahwa PESAN/PRODUK mengirim satu katalog kanonis, HARGA mengirim pricelist, MENU mengirim kategori, teks tak terduga dibalas singkat, dan setiap state aktif memiliki respons untuk MENU/BATAL/GANTI KOTA.
- [ ] **Step 2: Run focused test** dan pastikan gagal pada perilaku lama.
- [ ] **Step 3: Implement routing katalog kanonis, kota sebelum katalog, txt unexpected singkat, dan escape global tanpa state buntu.
- [ ] **Step 4: Run focused test** dan seluruh test WhatsApp terkait; perbaiki regresi sesuai kontrak baru.
- [ ] **Step 5: Commit** `feat(bot): satukan pintu katalog dan pastikan setiap state punya jalan keluar`.

### Task 3: Form Linear dan Dua Skenario End-to-End

**Files:**
- Modify: `app/Services/WhatsApp/OrderBotService.php`
- Test: `tests/Feature/WhatsApp/OrderBotFlowSimplificationTest.php`
- Update regressions: `tests/Feature/WhatsApp/OrderBotSimpleFlowTest.php`, `tests/Feature/WhatsApp/OrderBotCharacterizationTest.php`

**Interfaces:**
- Consumes: katalog kanonis dari Task 2
- Produces: urutan field `nama → alamat → nomor penerima → tanggal`

- [ ] **Step 1: Write failing E2E test** pelanggan baru Surabaya sampai `ORDER_CONFIRMED`.
- [ ] **Step 2: Write failing E2E test** pelanggan lama Malang yang mengganti kota di tengah keranjang lalu checkout.
- [ ] **Step 3: Run focused tests** dan pastikan gagal pada urutan/perpindahan lama.
- [ ] **Step 4: Implement urutan form linear, validasi nomor penerima di ringkasan, dan continuation setelah ganti kota.
- [ ] **Step 5: Run focused tests** dan seluruh test WhatsApp terkait; pastikan hijau.
- [ ] **Step 6: Commit** `feat(bot): alur form linear sampai pembayaran`.

### Task 4: Dokumentasi, Audit, dan Commit Rilis Fase 9

**Files:**
- Modify: `docs/ALUR_BOT_PESANAN.md`
- Modify: `docs/IMPLEMENTATION_PROGRESS.md`

- [ ] **Step 1: Update `ALUR_BOT_PESANAN.md`** dengan diagram Mermaid, perintah, state, resolver kota/harga, dan footer per state.
- [ ] **Step 2: Update progress log** dengan peta kondisi nyata, keputusan, bukti test, R-03 SELESAI, dan R-07 OPEN.
- [ ] **Step 3: Run full verification** `php artisan test --compact` dan Pint untuk seluruh file yang disentuh.
- [ ] **Step 4: Review git diff** untuk secret, file besar, dan perubahan di luar scope.
- [ ] **Step 5: Commit** `docs(bot): dokumentasikan alur chat Fase 9` dan pastikan branch `rev/fase-9-alur` bersih.
