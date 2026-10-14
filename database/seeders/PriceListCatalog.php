<?php

namespace Database\Seeders;

/**
 * Satu-satunya sumber data katalog resmi "Kue Pandan Asli".
 *
 * Disalin 1:1 dari docs/PRICE_LIST.md (price list dari owner, 14 Okt 2026).
 * Dipakai oleh:
 * - ProductSeeder (fresh install: truncate + isi ulang)
 * - PriceListSyncSeeder (server produksi: upsert aman tanpa hapus data lapak/order)
 *
 * Format tiap item:
 *   category    : slug kategori (produk|hampers|tumpeng)
 *   name        : nama produk (unik)
 *   description : deskripsi resmi price list (+ rincian isi untuk paket)
 *   image       : path di public/
 *   tag         : label price list (Ala Carte|Kue Mix|Hampers)
 *   variants    : [ [nama varian, harga rupiah], ... ]
 */
class PriceListCatalog
{
    public static function data(): array
    {
        return [
            // ---------------- ALA CARTE (kategori: produk) ----------------
            [
                'category' => 'produk',
                'name' => 'Kue Ijo',
                'description' => 'Kue ijo adalah kue tradisional terbuat dari tepung tapioka dengan warna hijaunya asli khas harum daun pandan segar. Teksturnya super kenyal dan lembut, wangi dengan taburan kelapa. Menambah cita rasa gurih dan sedikit manis yang menjadikan kue Ijo cocok dinikmati pada suasana apapun.',
                'image' => 'assets/homepage/product/kue-ijo.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Isi 3 Kemasan Mika', 9000],
                    ['Isi 5 Kemasan Mika', 15000],
                    ['Isi 12 Kemasan Thinwall', 40000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Ongol',
                'description' => 'Kue Ongol kami terbuat dari tepung tapioka yang ditambahkan gula merah jawa dan sedikit tambahan air pandan asli membuat warna kue ini bewarna coklat cantik. Kue yang memiliki tekstur kenyal dan legit dan terasa manis dimulut. Kue Ongol yang memiliki wangi khas gula jawa merah sangat cocok dipadukan dengan parutan kelapa segar yang akan memadukan antara gurih dan manis pada kue ini.',
                'image' => 'assets/homepage/product/kue-ongol.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Isi 10 Kemasan Mika', 10000],
                    ['Isi 30 Kemasan Thinwall', 40000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Pulut Srikaya',
                'description' => 'Kue Pulut yang dibuat dari beras ketan utuh yang akan menciptakan tekstur punel dan sedikit legit. Perpaduan warna Putih dari beras ketan dan Ungu alami dari bunga telang (butterfly pea tea) menambah keindahan visual pada kue ini. Kue Pulut ini dilengkapi dengan saus srikaya yang membuat perpaduan antara manis dan sedikit gurih dari saus dan gurih dari kue pulut itu sendiri menjadikan perpaduan rasa yang lengkap dan lezat.',
                'image' => 'assets/homepage/product/kue-pulut.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Isi 5 Kemasan Mika', 17500],
                    ['Isi 10 Kemasan Thinwall', 40000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Selai Srikaya',
                'description' => 'Selai yang terbuat dari perpaduan santan kelapa, telur, air pandan yang menghasilkan rasa yang gurih dan manis pada selai ini. Selai yang memiliki tekstur kental dan memiliki warna oren pekat dihasilkan dari telur. Selai itu cocok dipadukan dengan kue yang memiliki cita rasa netral maupun gurih untuk menambahkan cita rasa manis pada rasa kue tersebut.',
                'image' => 'assets/homepage/product/selai-srikaya.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['1 Botol', 60000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Lumpur Surga',
                'description' => 'Kue yang memiliki dua lapisan yaitu lapisan bawah bewarna hijau yang dihasilkan dari air pandan asli dan lapisan atas mirip dengan vla yang terbuat dari santan. Kue yang memiliki tekstur lembut dan lumer dimulut sangat nikmat jika disantap dalam keadaan dingin. Memiliki Cita rasa manis dari kue nya dan gurih asin dari vla nya berpadu menghasilkan rasa nikmat yang nyaman ketika masuk dimulut.',
                'image' => 'assets/homepage/product/kue-lumpur-surga.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Cup 100 ml', 6000],
                    ['Cup 200 ml', 12000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Ubi Nanas',
                'description' => 'Kue Ubi Nanas Adalah Kue yang terbuat dari Singkong atau ubi kayu yang empuk dan dikombinasi dengan buah nanas serta ditaburi parutan kelapa. Kue Dengan rasa manis gurih serta terdapat rasa asam sedikit sehingga cocok dinikmati pada suasana kapanpun.',
                'image' => 'assets/homepage/product/kue-ubi-nanas.jpeg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Isi 4 Kemasan Mika', 10000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Koci Ketan Hitam',
                'description' => 'Kue Koci Ketan Hitam Adalah Kue yang terbuat dari ketan hitam pilihan yang telah dihaluskan. Dengan isian kelapa parut dan gula merah dan dilengkapi santan yang telah dikukus bagian luar kue yang membuat kue ini terasa manis gurih dan bertekstur kenyal. Berbentuk segitiga kerucut sehingga memudahkan konsumen saat menggigit kue dan merasakan sensasi rasa dan tekstur kue.',
                'image' => 'assets/homepage/product/kue-koci-ketan-hitam.jpg',
                'tag' => 'Ala Carte',
                'variants' => [
                    ['Isi 6 Kemasan Thinwall', 40000],
                ],
            ],

            // ---------------- KUE MIX (kategori: produk, tag: Kue Mix) ----------------
            [
                'category' => 'produk',
                'name' => 'Kue Mix Mini',
                'description' => 'Kue MIX mini (Kemasan Mika) Berisi: Kue ijo 3 pcs, Kue Ongol-ongol 4 pcs dan Kue Pulut Srikaya 2 pcs. Kue ini cocok untuk dijadikan sebagai oleh-oleh atau sebagai cemilan ringan.',
                'image' => 'assets/homepage/product/kue-mix-mini.jpeg',
                'tag' => 'Kue Mix',
                'variants' => [
                    ['Paket Mix Mini (Ijo 3, Ongol 4, Pulut 2)', 25000],
                ],
            ],
            [
                'category' => 'produk',
                'name' => 'Kue Mix Ijo Pulut',
                'description' => 'Kue MIX (Kemasan Thinwall) Berisi: Kue ijo 4 pcs, Kue Pulut Srikaya 5 pcs. Kue dengan varian Best Seller ini cocok untuk dijadikan sebagai oleh-oleh atau sebagai cemilan ringan.',
                'image' => 'assets/homepage/product/kue-mix-kueijo.jpeg',
                'tag' => 'Kue Mix',
                'variants' => [
                    ['Paket Mix Ijo Pulut (Ijo 4, Pulut 5)', 40000],
                ],
            ],

            // ---------------- HAMPERS (kategori: hampers) ----------------
            [
                'category' => 'hampers',
                'name' => 'Hampers A (Anggun)',
                'description' => 'Hadirkan kehangatan dan kesan elegan melalui Hampers A (Anggun), perpaduan aneka kue tradisional pilihan dengan cita rasa khas Nusantara dan kemasan cantik yang berkelas. Cocok sebagai buah tangan, bingkisan keluarga, hadiah untuk kolega, maupun hantaran di momen spesial. Berisi: Kue Ijo (12 pcs), Kue Ongol (30 pcs), Kue Pulut (10 pcs), Lumpur Surga (4 cup 160ml).',
                'image' => 'assets/homepage/product/hampers-a.jpg',
                'tag' => 'Hampers',
                'variants' => [
                    ['Paket A', 160000],
                ],
            ],
            [
                'category' => 'hampers',
                'name' => 'Hampers B (Bagus)',
                'description' => 'Hampers B (Bagus) menyajikan perpaduan kue tradisional favorit dalam kemasan premium bernuansa alami. Pilihan tepat untuk berbagi kebahagiaan, mempererat silaturahmi, dan memberikan apresiasi kepada orang-orang tersayang dengan sajian yang istimewa. Berisi: Kue Ijo (12 pcs), Kue Ongol (30 pcs), Kue Pulut (10 pcs).',
                'image' => 'assets/homepage/product/hampers-b.jpg',
                'tag' => 'Hampers',
                'variants' => [
                    ['Paket B', 130000],
                ],
            ],
            [
                'category' => 'hampers',
                'name' => 'Hampers C (Cantik)',
                'description' => 'Kenalkan kelezatan kue tradisional melalui Hampers C (Cantik), paket pilihan dengan variasi rasa dan tekstur yang menggugah selera. Dikemas praktis dan menarik, hampers ini cocok untuk buah tangan, bingkisan sederhana, atau pelengkap momen kebersamaan. Berisi: Kue Ijo (5 pcs), Kue Ongol (12 pcs), Kue Pulut (4 pcs), Kue Ubi Nanas (8 pcs).',
                'image' => 'assets/homepage/product/hampers-c.png',
                'tag' => 'Hampers',
                'variants' => [
                    ['Paket C', 65000],
                ],
            ],

            // ---------------- TUMPENG (kategori: tumpeng) ----------------
            [
                'category' => 'tumpeng',
                'name' => 'Tumpeng Mini Mix',
                'description' => 'Sajian aneka kue tradisional dalam tampilan tumpeng mini yang cantik dan praktis. Perpaduan warna alami, tekstur, dan cita rasa khas Nusantara menjadikannya pilihan menarik untuk merayakan momen sederhana maupun istimewa. Cocok untuk syukuran, ulang tahun, arisan, dan acara keluarga. Berisi: Kue Ijo (25 pcs), Kue Pulut (20 pcs), Kue Ongol (50 pcs), Lumpur Surga (6 cup).',
                'image' => 'assets/homepage/product/tumpeng-mini.jpg',
                'tag' => 'Hampers',
                'variants' => [
                    ['Paket Mini', 250000],
                ],
            ],
            [
                'category' => 'tumpeng',
                'name' => 'Tumpeng Besar Mix',
                'description' => 'Jadikan momen kebersamaan semakin berkesan dengan Tumpeng Besar Mix dari Kue Pandan Asli. Sajian aneka kue tradisional dalam porsi lebih besar, ditata menarik di atas alas daun pisang untuk menghadirkan nuansa tradisional yang autentik dengan tampilan premium. Ideal untuk acara kantor, perayaan keluarga, arisan besar, syukuran, dan berbagai perayaan spesial. Berisi: Kue Ijo (50 pcs), Kue Pulut (40 pcs), Kue Ongol (100 pcs), Lumpur Surga (12 cup).',
                'image' => 'assets/homepage/product/tumpeng-besar.jpg',
                'tag' => 'Hampers',
                'variants' => [
                    ['Paket Besar', 500000],
                ],
            ],
        ];
    }
}
