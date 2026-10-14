<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeder katalog resmi "Kue Pandan Asli".
 *
 * Sumber kebenaran: docs/PRICE_LIST.md (price list dari owner, 14 Okt 2026).
 * Kalau harga berubah, ubah di PRICE_LIST.md DULU lalu sinkronkan ke sini.
 *
 * Struktur kategori dipertahankan (Produk/Hampers/Tumpeng) karena dipakai
 * filter homepage (resources/views/livewire/homepage.blade.php) dan
 * bot WhatsApp (OrderBotService::sendProductsByCategory).
 * - "Ala Carte" & "Kue Mix" dari price list -> kategori Produk (pembeda via kolom tag)
 * - Tumpeng Mini/Besar -> kategori Tumpeng (price list mengelompokkannya ke Hampers,
 *   jadi tag-nya ikut "Hampers" agar pencarian bot tetap ketemu)
 *
 * Semua produk resmi bersifat global: region_id = null (bot menampilkan
 * produk region user + produk global).
 */
class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Kosongkan tabel untuk menghindari duplikasi saat seeding ulang
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Category::truncate();
        Product::truncate();
        DB::table('product_variants')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $data = PriceListCatalog::data();

        $categories = [];
        foreach (['produk' => 'Produk', 'hampers' => 'Hampers', 'tumpeng' => 'Tumpeng'] as $slug => $name) {
            $categories[$slug] = Category::create(['name' => $name, 'slug' => $slug]);
        }

        foreach ($data as $item) {
            $product = $categories[$item['category']]->products()->create([
                'name' => $item['name'],
                'description' => $item['description'],
                'image_path' => $item['image'],
                'tag' => $item['tag'],
                'is_active' => true,
                'region_id' => null,
            ]);

            $variants = [];
            foreach ($item['variants'] as $variant) {
                $variants[] = [
                    'name' => $variant[0],
                    'price' => $variant[1],
                    'is_active' => true,
                ];
            }
            $product->variants()->createMany($variants);
        }
    }
}
