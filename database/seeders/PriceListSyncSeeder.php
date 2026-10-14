<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

/**
 * Sinkronisasi katalog resmi ke database PRODUKSI secara aman.
 *
 * - TIDAK truncate, TIDAK menghapus produk/varian lain (produk KHUSUS LAPAK,
 *   Ranch Kawi, SUPERMARKET dan seluruh order/history tetap utuh).
 * - Produk resmi dicocokkan by NAMA (updateOrCreate): harga, deskripsi,
 *   gambar, tag, dan status dijamin sama dengan docs/PRICE_LIST.md.
 * - Varian resmi dicocokkan by (product_id, nama): varian usang milik produk
 *   resmi yang tidak ada di price list dinonaktifkan (is_active=false),
 *   bukan dihapus (order lama merujuk variant_id).
 * - Produk resmi selalu global (region_id=null) agar terlihat semua cabang.
 *
 * Jalankan: php artisan db:seed --class=PriceListSyncSeeder --force
 * Aman dijalankan berulang (idempotent).
 */
class PriceListSyncSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $names = ['produk' => 'Produk', 'hampers' => 'Hampers', 'tumpeng' => 'Tumpeng'];
        $categories = [];
        foreach ($names as $slug => $name) {
            $categories[$slug] = Category::firstOrCreate(['slug' => $slug], ['name' => $name]);
        }

        $touchedProducts = 0;
        $touchedVariants = 0;

        foreach (PriceListCatalog::data() as $item) {
            // Kunci PENTING: cocokkan by nama + region NULL. Produk lapak/cabang
            // (region_id terisi, mis. "Kue Lumpur Surga KHUSUS LAPAK" region 3)
            // TIDAK BOLEH tersentuh seeder ini — mereka milik operasional cabang.
            $product = Product::updateOrCreate(
                ['name' => $item['name'], 'region_id' => null],
                [
                    'category_id' => $categories[$item['category']]->id,
                    'description' => $item['description'],
                    'image_path' => $item['image'],
                    'tag' => $item['tag'],
                    'is_active' => true,
                    'region_id' => null,
                ]
            );
            $touchedProducts++;

            $wanted = [];
            foreach ($item['variants'] as [$variantName, $price]) {
                $variant = $product->variants()->updateOrCreate(
                    ['name' => $variantName],
                    ['price' => $price, 'is_active' => true]
                );
                $wanted[] = $variant->id;
                $touchedVariants++;
            }

            // Nonaktifkan (jangan hapus) varian usang produk resmi ini
            // agar riwayat order yang merujuk variant_id tetap valid.
            $product->variants()->whereNotIn('id', $wanted)->update(['is_active' => false]);
        }

        $this->command?->info("Price list tersinkron: {$touchedProducts} produk, {$touchedVariants} varian aktif.");
    }
}
