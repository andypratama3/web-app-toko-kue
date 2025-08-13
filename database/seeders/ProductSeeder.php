<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

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

        // 1. Buat Kategori
        $catProduk = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $catHampers = Category::create(['name' => 'Hampers', 'slug' => 'hampers']);
        $catTumpeng = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);

        // 2. Buat Produk untuk Kategori 'Produk'
        $kueIjo = $catProduk->products()->create([
            'name' => 'Kue Ijo',
            'description' => 'Kue Ijo terbuat dari tepung beras yang telah diayak lalu dicampur dengan campuran santan kelapa fresh dan air pandan asli. Kue ini memiliki tekstur yang kenyal dan lembut bersamaan ketika dikunyah didalam mulut. Dibalut taburan dengan kelapa parut segar menambah cita rasa gurih dan manis yang menjadikan kue Ijo cocok dinikmati pada suasana apapun.',
            'image_path' => 'assets/homepage/product/kue-ijo.jpg',
            'tag' => 'Ala Carte',
            'is_active' => true,
        ]);
        $kueIjo->variants()->createMany([
            ['name' => 'Isi 3 Kemasan Mika', 'price' => 9000],
            ['name' => 'Isi 5 Kemasan Mika', 'price' => 15000],
            ['name' => 'Isi 12 Kemasan Thinwall', 'price' => 36000],
        ]);

        $lumpurSurga = $catProduk->products()->create([
            'name' => 'Kue Lumpur Surga',
            'description' => 'Kue yang memiliki dua lapisan yaitu lapisan bawah bewarna hijau yang dihasilkan dari air pandan asli dan lapisan atas mirip dengan vla yang terbuat dari santan. Kue yang memiliki tekstur lembut dan lumer dimulut sangat nikmat jika disantap dalam keadaan dingin.',
            'image_path' => 'assets/homepage/product/kue-lumpur-surga.jpg',
            'tag' => 'Ala Carte',
            'is_active' => true,
        ]);
        $lumpurSurga->variants()->create(['name' => 'Per Cup', 'price' => 6000]);

        $ongol = $catProduk->products()->create([
            'name' => 'Kue Ongol Ongol',
            'description' => 'Kue Ongol kami terbuat dari tepung tapioka yang ditambahkan gula merah jawa dan sedikit tambahan air pandan asli membuat warna kue ini bewarna coklat cantik. Kue yang memiliki tekstur kenyal dan legit dan terasa manis dimulut.',
            'image_path' => 'assets/homepage/product/kue-ongol.jpg',
            'tag' => 'Ala Carte',
            'is_active' => true,
        ]);
        $ongol->variants()->createMany([
            ['name' => 'Isi 10 Kemasan Mika', 'price' => 10000],
            ['name' => 'Isi 30 Kemasan Thinwall', 'price' => 35000],
        ]);

        // 3. Buat Produk untuk Kategori 'Hampers'
        $hampersA = $catHampers->products()->create([
            'name' => 'Hampers A (Anggun)',
            'description' => 'Berisi: Kue Ijo (12 pcs), Kue Ongol-ongol (30 pcs), Kue Pulut Srikaya (10 pcs), Lumpur Surga (4 cup @100ml).',
            'image_path' => 'assets/homepage/product/hampers-a.jpg',
            'tag' => 'Hampers',
            'is_active' => true,
        ]);
        $hampersA->variants()->create(['name' => 'Paket A', 'price' => 160000]);

        // 4. Buat Produk untuk Kategori 'Tumpeng'
        $tumpengMini = $catTumpeng->products()->create([
            'name' => 'Tumpeng Mini Mix',
            'description' => 'Berisi: Kue Ijo (25 pcs), Kue Pulut (20 pcs), Kue Ongol-ongol (50 pcs), Lumpur Surga (6 cup). Cocok untuk syukuran, ulang tahun, atau acara spesial lainnya.',
            'image_path' => 'assets/homepage/product/tumpeng-mini.jpg',
            'tag' => 'Tumpeng',
            'is_active' => true,
        ]);
        $tumpengMini->variants()->create(['name' => 'Paket Mini', 'price' => 250000]);
    }
}
