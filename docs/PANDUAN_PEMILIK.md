# Panduan Pemilik — Harga per Kota & Nomor Admin

Panduan ini untuk mengisi harga varian per kota dan memastikan notifikasi pesanan
sampai ke admin/owner. Semua langkah bisa dijalankan tanpa mengubah kode.

## 1. Siapkan file CSV harga

Gunakan template [`docs/templates/harga-varian.csv`](templates/harga-varian.csv).
Kolom wajib, urutan bebas:

| Kolom | Isi | Contoh |
|---|---|---|
| `region` | Nama atau slug kota | `Surabaya`, `Malang` |
| `product` | Nama produk persis seperti di katalog | `Kue Pandan Asli` |
| `variant` | Nama varian persis seperti di katalog | `Reguler` |
| `price` | Harga angka; boleh `15000` atau `Rp 15.000` | `15000` |

Aturan:

- Nama kota, produk, dan varian harus sama dengan data di aplikasi (tidak peka huruf besar/kecil).
- Satu baris = satu kombinasi kota + produk + varian. Baris duplikat ditolak.
- Harga harus lebih dari 0.
- Baris yang salah membuat seluruh proses dibatalkan; tidak ada yang ditulis setengah jalan.

## 2. Validasi dulu (dry-run)

```bash
php artisan bot:import-prices docs/templates/harga-varian.csv
```

Perintah ini hanya menampilkan tabel `buat / perbarui / tetap` dan **tidak menulis apa pun**.
Periksa kota, produk, varian, dan harga pada tabel. Perbaiki CSV bila ada baris yang gagal.

## 3. Terapkan setelah yakin

```bash
php artisan bot:import-prices docs/templates/harga-varian.csv --apply
```

Sebelum `--apply` di production:

1. Backup database.
2. Pastikan migrasi `product_variant_prices` sudah dijalankan.
3. Simpan file CSV yang dipakai sebagai catatan.

Harga dasar di `product_variants.price` tidak diubah. Override hanya berlaku untuk
kota yang ada di CSV; kota lain tetap memakai harga dasar.

## 4. Nomor admin/owner

Notifikasi pesanan memakai urutan:

1. `BOT_ADMIN_PHONE` (satu nomor untuk semua kota);
2. `regions.owner_phone` (nomor owner kota) sebagai fallback.

Isi salah satunya:

```dotenv
BOT_ADMIN_PHONE=628xxxxxxxxxx
```

Atau isi `owner_phone` per kota di menu Pengaturan Kota. Setelah diisi, kirim pesan tes
dari halaman pengaturan untuk memastikan nomor menerima notifikasi.

## 5. Verifikasi setelah import

```bash
php artisan bot:audit-data
```

Periksa bahwa harga kota yang diimpor muncul, tidak ada varian tanpa harga, dan
region sudah punya nomor admin/owner. Bila `bot:audit-data` gagal karena kolom/tabel
belum ada, jalankan migrasi terlebih dahulu.

## 6. Batas saat ini

- Data harga Surabaya/Malang per varian belum tersedia (R-15); file contoh hanya format.
- R-07 (catatan klien terpotong) masih OPEN dan tidak memengaruhi import harga.
- Import tidak boleh dijalankan ke production tanpa persetujuan pemilik.
