# Deploy Foto Produk (R-02)

Dokumen ini menjelaskan cara memastikan foto produk tampil di halaman admin dan terkirim oleh bot WhatsApp.

## Ringkasan

Ada **dua lokasi foto** di aplikasi ini, dan keduanya harus ditangani:

| Asal foto | Nilai `products.image_path` | Lokasi file sebenarnya | URL publik |
| --- | --- | --- | --- |
| Katalog lama | `assets/homepage/product/kue-ijo.jpg` | `public/assets/homepage/product/kue-ijo.jpg` | `{APP_URL}/assets/homepage/product/kue-ijo.jpg` |
| Upload admin | `products/kue-baru.jpg` | `storage/app/public/products/kue-baru.jpg` | `{APP_URL}/storage/products/kue-baru.jpg` |

Semua pembacaan foto melewati satu helper, `app/Support/ProductImage.php`:

- `ProductImage::exists($path)` — cek file benar-benar ada.
- `ProductImage::url($path)` — URL siap pakai; jika file tidak ada, otomatis memakai placeholder.

Halaman admin memakai accessor `$product->image_url`, dan bot memakai helper yang sama. Jadi jika file hilang, UI tidak menampilkan gambar rusak dan bot mengirim teks saja.

## Langkah deploy

1. **Buat symlink storage publik.** Upload baru disimpan di disk `public`, sehingga butuh symlink:

   ```bash
   php artisan storage:link
   ```

   Hasil yang benar:

   ```bash
   ls -l public/storage
   # public/storage -> /path/ke/proyek/storage/app/public
   ```

   Jika `public/storage` sudah ada sebagai **direktori biasa** (bukan symlink), hapus dulu isinya lalu buat ulang:

   ```bash
   rm -rf public/storage
   php artisan storage:link
   ```

2. **Set disk default di `.env` produksi.**

   ```env
   FILESYSTEM_DISK=public
   ```

   `.env.example` sudah memakai nilai ini. Tanpa ini, upload baru bisa tersimpan di disk `local` yang tidak dapat diakses publik.

3. **Set `APP_URL` ke domain asli.**

   ```env
   APP_URL=https://domain-anda.com
   ```

   `ProductImage` membentuk URL absolut dari `config('app.url')`. Jika `APP_URL` masih `http://localhost`, bot akan mengirim link foto yang tidak bisa dibuka penerima dan browser admin akan menampilkan gambar rusak.

4. **Pastikan file katalog lama ikut ter-deploy.**

   ```bash
   ls public/assets/homepage/product | wc -l
   ```

   Jika hasilnya `0`, file katalog lama tidak ikut ter-upload. Deploy ulang folder `public/assets/homepage/product/` dari repository.

5. **Bersihkan cache konfigurasi setelah mengubah `.env`.**

   ```bash
   php artisan config:clear
   php artisan cache:clear
   ```

## Placeholder

Jika foto tidak ditemukan, aplikasi memakai `public/images/placeholder-product.png`. File ini ada di repository dan harus ikut ter-deploy. Jangan hapus folder `public/images/`.

## Verifikasi

1. Cek data dan file dalam satu perintah:

   ```bash
   php artisan bot:audit-data
   ```

   Bagian **FOTO PRODUK** akan menampilkan:

   - `Semua file foto produk ditemukan.` — semua aman.
   - Daftar produk dengan keterangan `TIDAK ADA` — file hilang; bot hanya mengirim teks, halaman admin memakai placeholder.

2. Buka halaman admin **Manajemen Produk** (index dan detail produk). Foto harus tampil, bukan ikon rusak.

3. Uji satu produk lewat WhatsApp: pilih produk dari katalog. Jika foto ada, bot mengirim gambar + caption; jika tidak, bot mengirim teks saja tanpa error.

## Catatan

- Produk yang fotonya hilang **tidak** membuat bot gagal; kegagalan kirim gambar dicatat di `storage/logs/whatsapp.log` dengan pesan `Gagal kirim foto produk, lanjut teks saja`.
- Menghapus produk lewat admin hanya menghapus file di disk `public`; file katalog lama di `public/assets/` tidak terhapus (tidak berbahaya, hanya sisa file).
- Untuk penyimpanan cloud (S3 dan sejenisnya) di masa depan, cukup ubah disk di `ProductImage`; path `assets/...` tetap dibaca dari `public/`.
