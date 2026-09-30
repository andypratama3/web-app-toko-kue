<?php

namespace Database\Seeders;

use App\Models\AdminNotification;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Seeder data demo untuk memeriksa tampilan responsif SELURUH halaman admin.
 *
 * Sengaja memakai nama/alaman/catatan yang PANJANG supaya kondisi
 * "kolom terpotong / teks meluber" yang dulu terjadi di halaman Customer
 * langsung terlihat kalau masih ada.
 *
 * Jalankan:  php artisan demo:seed
 * Hapus:     php artisan demo:seed --reverse
 *
 * (db:seed --class=DemoResponsiveSeeder juga bisa untuk isi data, tapi
 *  opsi --reverse tidak bisa dipakai lewat db:seed karena Artisan menolak
 *  opsi yang tidak dikenal — makanya dibungkus command demo:seed.)
 *
 * Aman dijalankan berulang: seeder menyimpan daftar id yang dibuatnya di
 * storage/app/demo_seeded.json. Kalau file itu masih ada, seeder menolak
 * jalan lagi (supaya data tidak dobel) dan menyuruh pakai --reverse dulu.
 *
 * Hanya untuk database lokal — guarded oleh konfigurasi APP_ENV.
 */
class DemoResponsiveSeeder extends Seeder
{
    private const PASSWORD = 'password';

    /** File manifest berisi id record hasil seeding ini. */
    private const MANIFEST = 'app/demo_seeded.json';

    /** id record yang dibuat seeder ini, per model. */
    private array $created = [];

    /**
     * `db:seed` menyusun definisi opsi sendiri, jadi opsi `--reverse` yang
     * didefinisikan di seeder tidak akan terdaftar. Cek definisi command dulu
     * (kalau suatu saat diregistrasikan), lalu jatuh ke argv.
     */
    private function wantsReverse(): bool
    {
        $command = $this->command;

        if ($command !== null && $command->getDefinition()->hasOption('reverse')) {
            return (bool) $command->option('reverse');
        }

        return in_array('--reverse', $_SERVER['argv'] ?? [], true);
    }

    private function manifestPath(): string
    {
        return storage_path(self::MANIFEST);
    }

    private function readManifest(): array
    {
        $path = $this->manifestPath();

        return File::exists($path) ? (json_decode(File::get($path), true) ?: []) : [];
    }

    /**
     * Catat id agar bisa dihapus persis oleh --reverse.
     * Jangan cast ke int: whatsapp_conversations & whatsapp_messages
     * memakai UUID sebagai primary key.
     */
    private function track(string $model, $id): void
    {
        if ($id === null || $id === '') {
            return;
        }

        $this->created[$model][] = $id;
    }

    private function saveManifest(): void
    {
        File::ensureDirectoryExists(dirname($this->manifestPath()));
        File::put(
            $this->manifestPath(),
            json_encode($this->created, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->error('Ditolak: seeder demo hanya boleh jalan di local/testing.');

            return;
        }

        if ($this->wantsReverse()) {
            $this->reverse();

            return;
        }

        $manifest = $this->readManifest();
        if (! empty($manifest)) {
            $total = array_sum(array_map('count', $manifest));
            $this->command?->error("Seeder demo sudah pernah jalan ({$total} record). Jalankan dulu:");
            $this->command?->comment('  php artisan demo:seed --reverse');

            return;
        }

        $regions = Region::orderBy('id')->get();
        if ($regions->isEmpty()) {
            $this->command?->error('Region kosong. Jalankan RoleAndUserSeeder dulu.');
            return;
        }

        $admin = User::whereHas('roles', fn ($q) => $q->where('name', 'admin'))->first()
            ?? User::find(1);
        $kurir = User::role('kurir')->get()->groupBy(fn ($u) => $u->region_id);
        $products = Product::with('variants')->get();
        $catProduk = Category::orderBy('id')->first();
        $custCats = CustomerCategory::orderBy('id')->get();
        $templates = WhatsAppTemplate::orderBy('id')->get();

        // 1) Zona pengiriman (dipakai halaman Chat / ongkir)
        foreach ($regions as $region) {
            foreach (['Kota Lama', 'Gubeng Baru', 'Pondok Indah', 'Cibinong Cheap'] as $i => $area) {
                DeliveryZone::firstOrCreate(
                    ['region_id' => $region->id, 'area_name' => $area],
                    ['distance_km' => 3 + $i * 4, 'ongkir' => 10000 + $i * 5000, 'is_active' => true]
                );
            }
        }

        // 2) Customer — nama & alamat sengaja panjang
        $customers = collect();
        $nama = [
            'Bapak Sutjianto Budiman laksana', 'Ibu Ratnaningsih', 'Toko Roti Manis Sejahtera Abadi',
            'Koperasi Serbaguna Mandiri', 'Bapak Hendra Wijaya Kusuma', 'Siti Aminah Putri',
            'Warung Rokok dan Ketelan Pak Budi', 'Bapak Yohanes Pontoh', 'Ibu Maria Kristina',
            'Toko Grosir Elektronik Nusantara Jaya', 'Rina Oktaviani', 'Agus Setiawan',
            'Bapak Bambang Prasetyo', 'Dewi Lestari', 'Toko Berkah Abundant', 'Bapak Fernandes Lim',
            'Indah Permatasari', 'Suprianto Hadi', 'Bapak Cornelius van Dijk', 'Nurul Hidayah',
        ];
        $alamat = [
            'Jl. Raya Darmo Permata III No. 45 RT 04 RW 09, Next to S-Mart, Surabaya',
            'Jl. Kenanga Raya Gg. Mawar No. 12, dekat Pasar Induk Kembang Kuning',
            'Kp. Cipaganti Raya No. 88, arah bundaran, Bandung Jawa Barat',
            'Jl. Ir. H. Juanda No. 301, seberang Gedung BNI, dekat-station',
        ];
        foreach ($nama as $i => $n) {
            $region = $regions[$i % $regions->count()];
            $customer = Customer::create([
                'name' => $n,
                'company_name' => $i % 3 === 0 ? null : 'CV ' . Str::upper(Str::random(4)) . ' Niaga',
                'address' => $alamat[$i % count($alamat)],
                'landmark' => $i % 2 ? 'dekat masjid besar' : null,
                'phone' => '0812' . str_pad((string) (100000000 + $i * 137), 9, '0', STR_PAD_LEFT),
                'opening_hours' => '08.00 - 17.00',
                'payment_type' => $i % 2 ? 'COD' : 'Transfer',
                'note' => $i % 5 === 0 ? 'Customer lama, biasanya order hampers untuk acara kantor' : null,
                'region_id' => $region->id,
                'customer_category_id' => $custCats->isNotEmpty()
                    ? $custCats[$i % $custCats->count()]->id : null,
                'added_by_user_id' => $admin?->id,
            ]);
            $this->track(Customer::class, $customer->id);
            $customers->push($customer);
        }
        // tandai 2 customer supaya ikon flag merah terlihat
        $customers[2]->update(['is_flagged' => true]);
        $customers[7]->update(['is_flagged' => true]);

        // 3) Order — kombinasi status & tanggal agar filter & chart terisi
        $status = [
            'pending', 'diterima_pembeli', 'diterima_pembeli', 'selesai', 'selesai', 'selesai',
            'menunggu_verifikasi_admin', 'diverifikasi_admin', 'dikembalikan', 'dibatalkan',
        ];
        $orders = collect();
        foreach ($customers as $i => $customer) {
            $jumlah = random_int(1, 3);
            for ($k = 0; $k < $jumlah; $k++) {
                $kurirUser = $kurir->get($customer->region_id)?->first() ?? $admin;
                $items = $products->shuffle()->take(random_int(1, 3));
                $total = 0;
                $tanggal = Carbon::now()->subDays(random_int(0, 45))->setTime(random_int(8, 20), random_int(0, 59));
                $st = $status[(($i * 3) + $k) % count($status)];

                $order = Order::create([
                    'invoice_number' => 'INV' . $tanggal->format('Ymd') . str_pad((string) ($i * 3 + $k), 4, '0', STR_PAD_LEFT),
                    'customer_id' => $customer->id,
                    'phone' => $customer->phone,
                    'address' => $customer->address,
                    'payment_method' => $customer->payment_type,
                    'payment_proof' => null,
                    'note' => $k === 0 ? 'Tolong dibungkus bagus ya, ada acara syukuran' : null,
                    'created_by_user_id' => $kurirUser?->id ?? $admin?->id,
                    'region_id' => $customer->region_id,
                    'status' => $st,
                    'channel' => 'whatsapp',
                    'paid_at' => $st === 'pending' ? null : $tanggal->copy()->addHours(2),
                    'picked_up_at' => in_array($st, ['selesai', 'diverifikasi_admin', 'dikembalikan']) ? $tanggal->copy()->addDay() : null,
                    'delivered_at' => in_array($st, ['selesai', 'diverifikasi_admin']) ? $tanggal->copy()->addDay()->addHours(3) : null,
                    'received_by_buyer_at' => $st === 'selesai' ? $tanggal->copy()->addDay()->addHours(4) : null,
                ]);
                $this->track(Order::class, $order->id);

                foreach ($items as $p) {
                    $v = $p->variants->first();
                    $qty = random_int(1, 4);
                    $price = $v?->price ?? 50000;
                    $this->track(OrderItem::class, OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $p->id,
                        'product_name' => $p->name,
                        'variant_id' => $v?->id,
                        'variant_name' => $v?->name,
                        'quantity' => $qty,
                        'price' => $price,
                        'subtotal' => $qty * $price,
                    ])->id);
                    $total += $qty * $price;
                }
                $order->update(['total_amount' => $total]);
                $orders->push($order);
            }
        }

        // 4) Notifikasi admin
        $titles = [
            ['order', 'Pesanan baru masuk', 'Customer memesan produk, segera diproses ya.'],
            ['verification', 'Pembayaran perlu diverifikasi', 'Ada bukti pembayaran baru masuk.'],
            ['return', 'Retur memerlukan konfirmasi', 'Pengajuan retur dari kurir.'],
            ['system', 'Broadcast selesai', 'Broadcast mingguan berhasil dikirim.'],
        ];
        foreach (range(1, 12) as $i) {
            [$type, $title, $msg] = $titles[$i % count($titles)];
            $order = $orders->get($i % max($orders->count(), 1));
            $this->track(AdminNotification::class, AdminNotification::create([
                'user_id' => $admin?->id,
                'region_id' => $order?->region_id ?? $regions[0]->id,
                'order_id' => $order?->id,
                'type' => $type,
                'title' => $title,
                'message' => $msg,
                'is_read' => $i > 6,
            ])->id);
        }

        // 5) Broadcast + penerima
        $bStatuses = ['draft', 'queued', 'processing', 'completed', 'failed'];
        foreach (range(1, 5) as $i) {
            $region = $regions[$i % $regions->count()];
            $custs = $customers->where('region_id', $region->id)->values();
            $st = $bStatuses[$i % count($bStatuses)];
            $b = WhatsAppBroadcast::create([
                'user_id' => $admin?->id,
                'region_id' => $region->id,
                'whatsapp_template_id' => $templates->isNotEmpty() ? $templates[$i % $templates->count()]->id : null,
                'title' => 'Promo ' . ['Ramadan', 'Tahun Baru', 'Valentine', 'Idul Fitri', 'Gajian'][$i % 5],
                'body_preview' => 'Halo kak, ada promo menarik! Cek katalog kami sekarang juga.',
                'recipient_filter' => ['region' => $region->name],
                'recipient_count' => $custs->count(),
                'sent_count' => $st === 'completed' ? $custs->count() : random_int(0, $custs->count()),
                'failed_count' => $st === 'failed' ? random_int(1, 3) : 0,
                'status' => $st,
                'scheduled_at' => Carbon::now()->subDays($i),
                'started_at' => $st !== 'draft' ? Carbon::now()->subDays($i)->addHour() : null,
                'finished_at' => in_array($st, ['completed', 'failed']) ? Carbon::now()->subDays($i)->addHours(2) : null,
            ]);
            $this->track(WhatsAppBroadcast::class, $b->id);
            foreach ($custs as $c) {
                $this->track(WhatsAppBroadcastRecipient::class, WhatsAppBroadcastRecipient::create([
                    'broadcast_id' => $b->id,
                    'customer_id' => $c->id,
                    'region_id' => $region->id,
                    'phone' => $c->phone,
                    'name' => $c->name,
                    'status' => $st === 'completed' ? 'sent' : ($st === 'failed' ? 'failed' : 'pending'),
                    'error' => $st === 'failed' ? 'Nomor tidak aktif' : null,
                    'sent_at' => $st === 'completed' ? Carbon::now()->subDays($i) : null,
                ])->id);
            }
        }

        // 6) Percakapan WhatsApp (halaman Chat)
        foreach ($customers->take(6) as $c) {
            $conv = WhatsAppConversation::create([
                'phone_number' => $c->phone,
                'profile_name' => $c->name,
                'customer_id' => $c->id,
                'region_id' => $c->region_id,
                'status' => 'active',
                'current_state' => 'idle',
                'message_count' => 4,
                'last_message_at' => Carbon::now()->subMinutes(random_int(5, 600)),
            ]);
            $this->track(WhatsAppConversation::class, $conv->id);
            $percakapan = [
                ['customer', 'Halo kak, masih ada produk hampers?'],
                ['business', 'Halo kak, selamat datang! Boleh. Mau pesan untuk acara apa?'],
                ['customer', 'Untuk acara syukuran, sekitar 20 pax.'],
                ['business', 'Siap kak. Untuk 20 pax saya rekomendasikan hamper premium.'],
            ];
            foreach ($percakapan as $t => [$sender, $isi]) {
                $this->track(WhatsAppMessage::class, WhatsAppMessage::create([
                    'conversation_id' => $conv->id,
                    'whatsapp_message_id' => 'wamid.DEMO' . Str::random(20),
                    'sender_type' => $sender,
                    'message_type' => 'text',
                    'content' => $isi,
                    'status' => 'delivered',
                ])->id);
            }
        }

        $this->saveManifest();

        $jumlah = fn (string $m) => count($this->created[$m] ?? []);
        $this->command?->info('Demo data siap: '
            . $jumlah(Customer::class) . ' customer, '
            . $jumlah(Order::class) . ' order, '
            . $jumlah(OrderItem::class) . ' item, '
            . $jumlah(AdminNotification::class) . ' notifikasi, '
            . $jumlah(WhatsAppBroadcast::class) . ' broadcast, '
            . $jumlah(WhatsAppConversation::class) . ' percakapan.');
        $this->command?->comment('Buka /admin lalu perkecil layar ke 375px untuk cek responsif.');
        $this->command?->comment('Bersihkan dengan: php artisan demo:seed --reverse');
    }

    /**
     * Hapus tepat record yang dibuat seeder ini, anak dulu lalu induknya.
     * DeliveryZone sengaja tidak dihapus: dipakai firstOrCreate dan bisa saja
     * sudah ada sebelum seeding.
     */
    private function reverse(): void
    {
        $manifest = $this->readManifest();
        if (empty($manifest)) {
            $this->command?->warn('Tidak ada data demo tersimpan — tidak ada yang dihapus.');

            return;
        }

        // Urutan: turunan lebih dulu supaya tidak ada FK yatim.
        $urutan = [
            WhatsAppMessage::class,
            WhatsAppConversation::class,
            WhatsAppBroadcastRecipient::class,
            WhatsAppBroadcast::class,
            AdminNotification::class,
            OrderItem::class,
            Order::class,
            Customer::class,
        ];

        $total = 0;
        foreach ($urutan as $model) {
            $ids = $manifest[$model] ?? [];
            if ($ids === []) {
                continue;
            }
            $jumlah = $model::whereIn('id', $ids)->delete();
            $total += $jumlah;
            $this->command?->line('  - ' . class_basename($model) . ': ' . $jumlah);
        }

        File::delete($this->manifestPath());

        $this->command?->info("Data demo dihapus: {$total} record.");
    }
}
