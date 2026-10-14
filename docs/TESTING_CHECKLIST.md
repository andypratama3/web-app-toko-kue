# Checklist Uji Manual + Monitoring Server
> Dibuat 14 Okt 2026 pasca sync price list & hardening alur order.
> Owner uji manual → kabari → AI cek log/DB server.

## 1. Skenario uji (owner)

### A. Web kurir (1 order test)
1. Login kurir → buat pesanan → pilih customer sendiri.
2. Tambah **Kue Ijo Isi 12** ×2 → total harus **Rp80.000**.
3. Ganti customer → pastikan cart me-refresh harga katalog (toast "harga disesuaikan").
4. Checkout → invoice terbit, format `INV/...`.
5. (Opsional, jika produk tanpa varian muncul di picker → harusnya hanya teks "hubungi admin", tanpa tombol tambah.)

### B. Bot WA (1 order test)
1. Kirim `PESAN` → isi nama → kirim `2x Kue Ijo`.
2. Bot harus balas **daftar 3 varian bernomor** (bukan langsung form).
3. Balas `3` → bot lanjut form penerima (bukan error).
4. Isi `Nama | Alamat | Tanggal | Jam` → pilih metode → ringkasan harus tulis **Varian: Isi 12, Qty: 2, TOTAL Rp80.000 + ongkir**.
5. Ketik `FIX` → order tercatat.
6. Uji salah ketik: `PESAN` baru → kirim `Kue Mars` → bot harus **langsung menolak** + sarankan katalog.
7. Uji menu: kirim `MENU` → balas `kue ijo` → harus langsung **detail Kue Ijo** (bukan daftar kategori).

### C. Cek harga cepat
- Homepage: Ijo 9/15/40rb, Mix Mini 25rb, Hampers B 130rb, Tumpeng Besar 500rb.
- Bot: kirim `HARGA` → pricelist sama dengan homepage.

## 2. Yang AI cek di server setelah owner selesai

```bash
# Masuk via: ssh -p 65002 u869297077@153.92.11.98

# 2a. Order test masuk & total benar
mysql → SELECT id,invoice_number,total_amount,status,channel,created_at FROM orders ORDER BY id DESC LIMIT 5;
mysql → SELECT product_name,variant_name,quantity,price,subtotal FROM order_items WHERE order_id=<ID>;

# 2b. Harga resmi tidak berubah (19 baris, bandingkan docs/PRICE_LIST.md)
mysql → SELECT p.name,v.name,v.price FROM products p JOIN product_variants v ON v.product_id=p.id
         WHERE p.region_id IS NULL ORDER BY p.id,v.price;

# 2c. Data lapak utuh (18 produk / 20 varian / 0 nonaktif abnormal)
mysql → SELECT COUNT(*) FROM products;              -- ekspektasi: 32 (18 lapak + 14 resmi)
mysql → SELECT COUNT(*) FROM product_variants WHERE is_active=0;  -- ekspektasi: 0

# 2d. Log error Laravel (checkout/bot)
tail -n 100 ~/domains/kuepandanasli.com/public_html/storage/logs/laravel.log | grep -iE "error|checkout|fail"

# 2e. Log bot WhatsApp
ls -lat ~/domains/kuepandanasli.com/public_html/storage/logs/ | head
tail -n 150 <file-whatsapp-log>.log | grep -iE "error|exception|gagal"

# 2f. Queue worker jalan (bot pakai queue?)
ps aux | grep -i "queue:work" | grep -v grep
php ~/domains/kuepandanasli.com/public_html/artisan queue:failed | tail -n 10
```

## 3. Waspada khusus (sisa temuan audit, belum di-fix)
1. `getLastOrder` tanpa scope kurir — cek akses log bila ada laporan intip data.
2. Bot terima tanggal/alamat bebas → waspadai order dengan tanggal aneh (`delivery_date` di note) & ongkir default Rp10.000.
3. Bukti bayar susulan setelah SKIP bisa orphan → cek order tanpa `payment_proof` + status gantung.
4. Invoice duplikat → cek `SELECT invoice_number, COUNT(*) c FROM orders GROUP BY invoice_number HAVING c>1;` harus kosong.

## 4. Rollback darurat (jika kacau)
- File: `*.bak-20261014` di tiap folder (controller, bot, routes, blade homepage).
- DB: `~/db-backups/dump-POST-SYNC-20261014-085954.sql.gz` (terbaru, sudah berisi harga resmi)
  atau `dump-PRE-SYNC-20261014-082913.sql.gz` (sebelum sync, harga lapak saja).
