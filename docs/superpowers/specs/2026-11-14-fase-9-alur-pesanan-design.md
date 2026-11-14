# Desain Fase 9 — Penyederhanaan Alur Pesanan

**Tanggal:** 14 November 2026  
**Status:** Disetujui melalui instruksi “continue” atas Prompt 3 Fase 9

## Tujuan
Membuat percakapan WhatsApp lebih linear dan tidak membingungkan tanpa membongkar state machine yang sudah bekerja.

## Alur Target
Sambutan → kota otomatis atau ditanya sekali → satu daftar produk → pilih nomor/nama → keranjang dan “Mau pesan apa?” → LANJUT → nama → alamat → nomor penerima → tanggal → metode/jam pengiriman → ringkasan dan ongkir → FIX → pembayaran → selesai.

## Pendekatan
1. Pertahankan enum state dan seluruh mesin state.
2. Satukan `PESAN` dan `PRODUK` ke satu method katalog kanonis.
3. Gunakan `OrderBotConversationState` sebagai sumber footer per state.
4. Pertahankan `ConversationRegionResolver` untuk kota dan `PriceResolver` untuk harga.
5. Simpan keranjang saat browsing atau ganti kota; batal hanya ketika pelanggan meminta BATAL.

## Batasan
- D1–D11 tidak diubah.
- `.env` tidak dibaca atau diubah; `BOT_ADMIN_PHONE` hanya ditambah ke `.env.example` dan config.
- R-07 tetap OPEN; tidak ada interpretasi baru atas teks klien yang terpotong.
- Migration dan struktur tabel tidak berubah pada Fase 9.
- Semua teks pelanggan WhatsApp tetap di bawah 4096 karakter.

## Keputusan Implementasi
- Nomor penerima tetap memakai `form_step = 6`, tetapi pertanyaan form mengikuti urutan target: nama → alamat → nomor penerima → tanggal.
- `footerFor(OrderBotConversationState)` menjadi sumber tunggal footer. Footer tidak mengulang perintah yang sudah ada di isi pertanyaan.
- `PESAN`, `PRODUK`, `HARGA`, dan `MENU` memiliki satu pintu masing-masing.
- Sesi idle tetap memakai `resetConversationIfStale()`; tidak ada mekanisme timeout baru.
