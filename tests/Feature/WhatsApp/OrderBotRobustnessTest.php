<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Hasil audit cross-check bot: guard cabang, keranjang lestari,
 * anti-ghost, salvage item, retry Meta — semua bug yang ditemukan
 * pada pemeriksaan menyeluruh.
 */
class OrderBotRobustnessTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected Region $otherRegion;
    protected Product $kueIjo;
    protected Product $tumpeng;
    protected OrderBotService $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        // Fake gambar saja di sini; fake Graph dipanggil per-test lewat
        // silenceMeta() supaya tidak menutupi fake khusus (mis. retry test).
        Http::fake([
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        $this->otherRegion = Region::create(['name' => 'Kota Surabaya', 'slug' => 'kota-surabaya']);

        $produk = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $this->kueIjo = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'ijo', 'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->kueIjo->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true]);
        $this->tumpeng = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Tumpeng Mini', 'description' => 'tumpeng', 'tag' => 'Tumpeng', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->tumpeng->id, 'name' => 'Isi 5', 'price' => 25000, 'is_active' => true]);

        $this->bot = app(OrderBotService::class);
    }

    /** Fake Graph Meta agar semua outbound diam-diam sukses. */
    protected function silenceMeta(): void
    {
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_ok']]], 200),
        ]);
    }

    protected function convo(array $context = [], string $state = 'AWAITING_ORDER_FORM'): WhatsAppConversation
    {
        // Semua test yang memicu outbound memakai helper ini — fake Graph
        // sekali di sini; test retry mem-fake sendiri tanpa lewat convo().
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_ok']]], 200),
        ]);

        return WhatsAppConversation::create([
            'phone_number' => '6281' . random_int(100000000, 999999999),
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::from($state)->value,
            'context' => array_merge(['location_done' => true, 'form_step' => 0], $context),
        ]);
    }

    protected function cartRow(Product $product, int $qty = 1): array
    {
        $variant = $product->variants()->first();

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'variant_id' => $variant->id,
            'variant_name' => $variant->name,
            'qty' => $qty,
        ];
    }

    // BUG-1: jawaban cabang setelah PESAN restart tidak boleh tersedot
    // jadi nama produk (state AWAITING_ORDER_FORM + awaiting_branch).
    public function test_jawaban_cabang_tidak_tersedot_ke_form_produk(): void
    {
        $c = $this->convo([
            'awaiting_branch' => true,
            'location_done' => false,
            'form_step' => null,
        ]);
        $this->bot->handleMessage($c, 'Surabaya');
        $c->refresh();
        $this->assertEquals($this->otherRegion->id, $c->region_id);
        $this->assertNull($c->getContext('awaiting_branch'));
        // Lanjut ke minta lokasi, bukan error "produk tidak ditemukan".
        $this->assertEquals(
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value,
            $c->current_state
        );
    }

    // BUG-1b: jawaban angka ("1") juga diterima sebagai pilihan cabang.
    public function test_jawaban_cabang_angka_tidak_jadi_nomor_katalog(): void
    {
        $c = $this->convo([
            'awaiting_branch' => true,
            'location_done' => false,
            'form_step' => null,
            'catalog_ids' => [$this->kueIjo->id],
        ]);
        $this->bot->handleMessage($c, '2'); // cabang ke-2 = Kota Surabaya
        $c->refresh();
        $this->assertEquals($this->otherRegion->id, $c->region_id);
        $this->assertEmpty($c->getContext('cart') ?? []);
    }

    // BUG-2: PESAN dari WELCOME_SENT (jalur stale-reset) mempertahankan keranjang.
    public function test_pesan_dari_welcome_sent_pertahankan_keranjang(): void
    {
        $c = $this->convo([
            'location_done' => false,
            'form_step' => 4,
        ], 'WELCOME_SENT');
        $c->update(['context' => array_merge($c->context ?? [], [
            'cart' => [$this->cartRow($this->kueIjo)],
            'order_customer' => ['name' => 'Budi'],
        ])]);

        $this->bot->handleMessage($c, 'PESAN');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart') ?? []);
        $this->assertEquals('Budi', $c->getContext('order_customer')['name'] ?? null);
        $this->assertEquals('cart', $c->getContext('after_location'));
    }

    // BUG-3: escape PRODUK dari tengah order tidak menghapus keranjang.
    public function test_produk_escape_pertahankan_keranjang(): void
    {
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo)],
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Oct 2026'],
            'form_step' => 5,
        ], 'ORDER_SUMMARY');

        $this->bot->handleMessage($c, 'PRODUK');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart') ?? []);
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $c->current_state);
    }

    // BUG-4: kurir internal tidak meminta pin GPS kedua kali bila ongkir
    // sudah dihitung dari lokasi awal.
    public function test_kurir_internal_tidak_minta_pin_ulang(): void
    {
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo)],
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Oct 2026'],
            'form_step' => 5,
            'distance_km' => 3.5,
            'ongkir' => 10000,
            'location_done' => true,
            'form_step' => 5,
        ], 'AWAITING_DELIVERY_METHOD');

        $this->bot->handleMessage($c, '3');
        $c->refresh();
        $this->assertEquals(
            OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            $c->current_state,
            'Kurir internal harus langsung ke pilihan slot, bukan minta pin lagi'
        );
        $this->assertEquals('internal_courier', $c->getContext('delivery_method'));
    }

    // BUG-5: mode ubah alamat menolak teks pendek ("FIX") tanpa menutup mode.
    public function test_ubah_alamat_tolak_teks_pendek(): void
    {
        $customer = ['name' => 'Budi', 'address' => 'Jl. Lama No 1 Denpasar', 'date' => '25 Oct 2026'];
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo)],
            'order_customer' => $customer,
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'editing_address' => true,
            'form_step' => 5,
        ], 'ORDER_SUMMARY');

        $this->bot->handleMessage($c, 'FIX');
        $c->refresh();
        $this->assertTrue((bool) $c->getContext('editing_address'), 'Mode ubah tetap aktif setelah teks pendek');
        $this->assertEquals('Jl. Lama No 1 Denpasar', $c->getContext('order_customer')['address']);
        $this->assertEquals(0, Order::count());

        // Alamat valid tetap diterima & menutup mode.
        $this->bot->handleMessage($c, 'Jl. Baru No 9 Denpasar');
        $c->refresh();
        $this->assertNull($c->getContext('editing_address'));
        $this->assertEquals('Jl. Baru No 9 Denpasar', $c->getContext('order_customer')['address']);
    }

    // BUG-6: customer_id null pada order orang lain TIDAK boleh dicocokkan
    // lewat orWhere — bukti/invoice nyasar ke order pelanggan lain.
    public function test_link_invoice_tidak_cocok_order_orang_lain(): void
    {
        $victim = Order::create([
            'invoice_number' => 'INV/0101/01/000/000/001',
            'customer_id' => (function () {
                $c = \App\Models\Customer::create(['phone' => '6281999999999', 'name' => 'Korban', 'region_id' => $this->region->id, 'address' => 'Jl. Dummy No 1']);
                return $c->id;
            })(),
            'phone' => '6281999999999',
            'address' => 'Jl. Korban No 1',
            'total_amount' => 9000,
            'payment_method' => 'qris',
            'status' => 'baru',
            'region_id' => $this->region->id,
            'channel' => 'whatsapp',
        ]);

        $intruder = $this->convo([], 'AWAITING_PAYMENT_PROOF');
        $this->bot->handleMessage($intruder, 'INV/0101/01/000/000/001');
        $intruder->refresh();
        $this->assertNull($intruder->getContext('confirmed_order_id'), 'Invoice orang lain tidak boleh tersambung');
    }

    // BUG-6b: milik sendiri (via phone) tetap tersambung.
    public function test_link_invoice_milik_sendiri_tetap_tersambung(): void
    {
        $c = $this->convo([], 'AWAITING_PAYMENT_PROOF');
        $mine = Order::create([
            'invoice_number' => 'INV/0101/01/000/000/002',
            'customer_id' => (function () use ($c) {
                $cu = \App\Models\Customer::create(['phone' => $c->phone_number, 'name' => 'Saya', 'region_id' => $this->region->id, 'address' => 'Jl. Saya No 1']);
                return $cu->id;
            })(),
            'phone' => $c->phone_number,
            'address' => 'Jl. Saya No 1',
            'total_amount' => 9000,
            'payment_method' => 'qris',
            'status' => 'baru',
            'region_id' => $this->region->id,
            'channel' => 'whatsapp',
        ]);

        $this->bot->handleMessage($c, 'INV/0101/01/000/000/002');
        $c->refresh();
        $this->assertEquals($mine->id, $c->getContext('confirmed_order_id'));
    }

    // BUG-7: restart PESAN membatalkan ghost order 'baru' tak berbayar.
    public function test_pesan_restart_batal_ghost_order(): void
    {
        $c = $this->convo(['form_step' => 5], 'AWAITING_PAYMENT_PROOF');
        $ghost = Order::create([
            'invoice_number' => 'INV/0101/01/000/000/003',
            'customer_id' => (function () use ($c) {
                $cu = \App\Models\Customer::create(['phone' => $c->phone_number, 'name' => 'Ghost', 'region_id' => $this->region->id, 'address' => 'Jl. Hantu No 1']);
                return $cu->id;
            })(),
            'phone' => $c->phone_number,
            'address' => 'Jl. Hantu No 1',
            'total_amount' => 9000,
            'payment_method' => 'qris',
            'status' => 'baru',
            'region_id' => $this->region->id,
            'channel' => 'whatsapp',
        ]);
        $c->update(['context' => array_merge($c->context ?? [], [
            'cart' => [$this->cartRow($this->kueIjo)],
            'confirmed_order_id' => $ghost->id,
        ])]);

        $this->bot->handleMessage($c, 'PESAN');
        $ghost->refresh();
        $this->assertEquals('dibatalkan', $ghost->status, 'Ghost order harus dibatalkan saat restart PESAN');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart') ?? []);
    }

    // BUG-8: PESAN berfungsi dari state MENU_SELECTION.
    public function test_pesan_dari_menu_selection(): void
    {
        $c = $this->convo([], 'MENU_SELECTION');
        $this->bot->handleMessage($c, 'PESAN');
        $c->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state);
        $this->assertTrue((bool) $c->getContext('awaiting_branch'), 'Langkah berikutnya = pilih cabang');
    }

    // BUG-9: di langkah 4, nama produk mengandung kata "menu"/"produk"
    // bukan perintah escape — keranjang tidak boleh hilang.
    public function test_langkah_4_nama_produk_berkata_menu_tidak_wipe_cart(): void
    {
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo)],
            'form_step' => 4,
        ]);
        $this->bot->handleMessage($c, 'Menu Pandan Spesial');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart') ?? [], 'Nama produk mengandung "menu" tidak boleh wipe cart');
        $this->assertEquals(4, $c->getContext('form_step'));
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state);
    }

    // BUG-9b: perintah PERSIS "MENU" di langkah 4 tetap mengembalikan ke menu.
    public function test_langkah_4_menu_persis_tetap_escape(): void
    {
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo)],
            'form_step' => 4,
        ]);
        $this->bot->handleMessage($c, 'MENU');
        $c->refresh();
        $this->assertEquals(OrderBotConversationState::MENU_SELECTION->value, $c->current_state);
    }

    // BUG-10: sendMessage Meta retry sekali pada 5xx lalu sukses.
    public function test_meta_retry_pada_5xx(): void
    {
        config([
            'services.whatsapp.phone_number_id' => '123456',
            'services.whatsapp.access_token' => 'test-token',
            'services.whatsapp.graph_version' => 'v21.0',
        ]);
        Http::fake([
            'graph.facebook.com/*' => Http::sequence()
                ->push('', 500)
                ->push(['messages' => [['id' => 'wamid_retry_ok']]], 200),
        ]);

        $id = app(WhatsappMetaService::class)->sendText('628111111111', 'halo coba retry', false);
        $this->assertNotNull($id);
        $this->assertEquals('wamid_retry_ok', $id);
    }

    // HAPUS n di luar rentang tidak boleh menghapus item lain diam-diam.
    public function test_hapus_nomor_di_luar_rentang_tidak_hapus_item(): void
    {
        $c = $this->convo([
            'cart' => [$this->cartRow($this->kueIjo), $this->cartRow($this->tumpeng)],
            'form_step' => 4,
        ]);
        $this->bot->handleMessage($c, 'HAPUS 9');
        $c->refresh();
        $this->assertCount(2, $c->getContext('cart') ?? [], 'HAPUS 9 dengan 2 item tidak boleh menghapus apa pun');
    }

    // Item dari cabang lain tidak lolos ke ringkasan; baris valid diselamatkan.
    public function test_item_cabang_lain_diselamatkan_bukan_seluruh_keranjang(): void
    {
        // Produk khusus cabang Surabaya — tidak sah untuk percakapan Denpasar.
        $cabangLain = Product::create([
            'category_id' => Category::first()->id,
            'region_id' => $this->otherRegion->id,
            'name' => 'Kue Khas Surabaya', 'description' => 'x', 'tag' => 'Produk', 'is_active' => true,
        ]);
        $v = ProductVariant::create(['product_id' => $cabangLain->id, 'name' => 'Isi 2', 'price' => 5000, 'is_active' => true]);

        $c = $this->convo([
            'cart' => [
                $this->cartRow($this->kueIjo),
                [
                    'product_id' => $cabangLain->id,
                    'product_name' => $cabangLain->name,
                    'variant_id' => $v->id,
                    'variant_name' => $v->name,
                    'qty' => 1,
                ],
            ],
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Oct 2026'],
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'form_step' => 5,
        ], 'ORDER_SUMMARY');

        $this->bot->handleMessage($c, 'FIX');
        $c->refresh();
        $cart = $c->getContext('cart') ?? [];
        $this->assertCount(1, $cart, 'Hanya baris tidak-sah yang dibuang');
        $this->assertEquals($this->kueIjo->id, $cart[0]['product_id']);
        // FIX memicu ringkasan ulang (karena ada yang dibuang), order belum dibuat
        // sampai FIX berikutnya.
        $this->assertEquals(0, Order::count());

        $this->bot->handleMessage($c, 'FIX');
        $this->assertEquals(1, Order::count());
    }

    // findProductByKeyword: wildcard LIKE user tidak membuat semua produk cocok.
    public function test_find_product_wildcard_tidak_match_semua(): void
    {
        $c = $this->convo();
        $method = new \ReflectionMethod(OrderBotService::class, 'findProductByKeyword');
        $method->setAccessible(true);
        $this->assertNull($method->invoke($this->bot, $c, '%'));
        $this->assertNull($method->invoke($this->bot, $c, '_'));
    }
}
