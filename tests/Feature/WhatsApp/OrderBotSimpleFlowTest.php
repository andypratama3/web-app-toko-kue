<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\CustomerCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Alur order baru (ramah 30+): lokasi → katalog → keranjang multi-produk →
 * nama → alamat → tanggal → metode → slot → ringkasan → FIX. Tanpa pipa.
 */
class OrderBotSimpleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $bot;
    protected Product $ijo;
    protected Product $tumpeng;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        CustomerCategory::create(['name' => 'Reseller']);

        $admin = User::create([
            'name' => 'Admin', 'email' => 'admin@test.com',
            'password' => bcrypt('password'), 'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        $produk = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $tumpengCat = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);

        $this->ijo = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'ijo', 'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 5', 'price' => 15000, 'is_active' => true]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 12', 'price' => 40000, 'is_active' => true]);

        $this->tumpeng = Product::create([
            'category_id' => $tumpengCat->id, 'region_id' => null,
            'name' => 'Tumpeng Mini Mix', 'description' => 'tumpeng', 'tag' => 'Hampers', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->tumpeng->id, 'name' => 'Paket Mini', 'price' => 250000, 'is_active' => true]);

        $this->bot = app(OrderBotService::class);
    }

    protected function newConversation(array $context = []): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => '6281000000001',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value,
            'context' => array_merge(['location_done' => true, 'form_step' => 0], $context),
        ]);
    }

    protected function driveToSummary(WhatsAppConversation $c): void
    {
        $this->bot->handleMessage($c, '2x Kue Ijo');
        $this->bot->handleMessage($c, '3'); // Isi 12
        $this->bot->handleMessage($c, 'Tumpeng Mini Mix');
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'Shinta');
        $this->bot->handleMessage($c, 'Jl. Mawar No 10 Surabaya');
        $this->bot->handleMessage($c, now()->addDays(3)->format('d M Y'));
        // stub metode+slot agar langsung ringkasan
        $c->setContext('delivery_method', 'self_pickup');
        $c->setContext('delivery_slot', '09:00-11:00');
        $c->setContext('ongkir', 0);
        $c->update(['current_state' => OrderBotConversationState::ORDER_SUMMARY->value]);
    }

    public function test_produk_multi_varian_minta_pilih(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');

        $c->refresh();
        $options = $c->getContext('pending_options');
        $this->assertCount(3, $options);
        $this->assertEquals('variant', $options[0]['type']);
    }

    public function test_keranjang_bisa_2_produk(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, '2x Kue Ijo');
        $this->bot->handleMessage($c, '3');
        $this->bot->handleMessage($c, 'Tumpeng Mini Mix');

        $c->refresh();
        $cart = $c->getContext('cart');
        $this->assertCount(2, $cart);
        $this->assertEquals(2, $cart[0]['qty']);
        $this->assertEquals(40000, ProductVariant::find($cart[0]['variant_id'])->price);
        $this->assertEquals(4, $c->getContext('form_step'));
    }

    public function test_lanjut_minta_nama_lalu_alamat_lalu_tanggal(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, 'LANJUT');

        $c->refresh();
        $this->assertEquals(1, $c->getContext('form_step'));

        $this->bot->handleMessage($c, 'Shinta');
        $c->refresh();
        $this->assertEquals(2, $c->getContext('form_step'));

        $this->bot->handleMessage($c, 'Jl');
        $c->refresh();
        $this->assertEquals(2, $c->getContext('form_step')); // terlalu pendek → tetap

        $this->bot->handleMessage($c, 'Jl. Mawar No 10 Surabaya');
        $c->refresh();
        $this->assertEquals(3, $c->getContext('form_step'));

        $this->bot->handleMessage($c, 'kemarin');
        $c->refresh();
        $this->assertEquals(3, $c->getContext('form_step')); // tanggal lewat → tetap
    }

    public function test_fix_membuat_order_multi_item_dengan_total_benar(): void
    {
        $c = $this->newConversation();
        $this->driveToSummary($c);

        $c->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $c->current_state);

        $this->bot->handleMessage($c, 'FIX');

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        // 2x40000 + 1x250000
        $this->assertEquals(330000, $order->total_amount);
        $this->assertCount(2, $order->items);
        $this->assertEquals('Shinta', $order->customer->name);
    }

    public function test_fix_ganda_tidak_membuat_order_ganda(): void
    {
        $c = $this->newConversation();
        $this->driveToSummary($c);
        $this->bot->handleMessage($c, 'FIX');
        $this->assertEquals(1, Order::count());

        $c->update(['current_state' => OrderBotConversationState::ORDER_SUMMARY->value]);
        $this->bot->handleMessage($c, 'FIX');

        $this->assertEquals(1, Order::count());
    }

    public function test_hapus_keluarkan_item_terakhir(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, 'HAPUS');

        $c->refresh();
        $this->assertEquals([], $c->getContext('cart') ?? []);
        $this->assertEquals(0, $c->getContext('form_step'));
    }

    public function test_produk_tidak_ada_langsung_ditolak(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Mars');

        $c->refresh();
        $this->assertEquals([], $c->getContext('cart') ?? []);
        $this->assertEquals(0, $c->getContext('form_step'));
    }

    public function test_jumlah_tanpa_x_dimengerti(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, '2 kue ijo');

        $c->refresh();
        $this->assertNotEmpty($c->getContext('pending_options'));
        $this->assertEquals(2, $c->getContext('product_quantity'));
    }

    public function test_teks_mengandung_selesai_bukan_perintah_lanjut(): void
    {
        // Regresi chat asli: "1x selesai serikaya" mental ke data penerima.
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, '1x selesai serikaya');

        $c->refresh();
        // Tetap di langkah tambah (4), BUKAN melompat ke nama penerima (1).
        $this->assertEquals(4, $c->getContext('form_step'));
        $this->assertNull($c->getContext('order_customer')['name'] ?? null);
    }

    public function test_nama_mengandung_sapaan_diterima(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'Halo, saya Budi Santoso');

        $c->refresh();
        $this->assertEquals(2, $c->getContext('form_step'));
        $this->assertEquals('Halo, saya Budi Santoso', $c->getContext('order_customer')['name']);
    }

    public function test_batal_pasca_fix_membatalkan_ghost_order(): void
    {
        $c = $this->newConversation();
        $this->driveToSummary($c);
        $this->bot->handleMessage($c, 'FIX');
        $this->assertEquals('baru', Order::first()->status);

        $this->bot->handleMessage($c, 'BATAL');

        $this->assertEquals('dibatalkan', Order::first()->fresh()->status);
    }

    public function test_ubah_alamat_via_pin_gps(): void
    {
        $c = $this->newConversation();
        $this->driveToSummary($c);
        $this->bot->handleMessage($c, 'ubah alamat');
        $c->refresh();
        $this->assertTrue((bool) $c->getContext('editing_address'));

        $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);
        $c->refresh();
        $this->assertNull($c->getContext('editing_address'));
        $this->assertStringContainsString(
            'Lokasi GPS', $c->getContext('order_customer')['address'] ?? ''
        );
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $c->current_state);
    }

    public function test_bahasa_pembeli_varian_otomatis(): void
    {
        // "Kue ijo isi 3, 2 pcs" → produk Kue Ijo + varian Isi 3 + qty 2, langsung keranjang.
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue ijo isi 3, 2 pcs');

        $c->refresh();
        $cart = $c->getContext('cart') ?? [];
        $this->assertCount(1, $cart);
        $this->assertEquals(2, $cart[0]['qty']);
        $variant = ProductVariant::find($cart[0]['variant_id']);
        $this->assertEquals('Isi 3', $variant->name);
        $this->assertEmpty($c->getContext('pending_options') ?? []);
    }

    public function test_tempel_format_lama_langsung_dipakai(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, 'LANJUT');

        $date = now()->addDays(6)->format('d M Y');
        $this->bot->handleMessage($c, "Rahma | Jl Kawi no 8 Malang | {$date}");

        $c->refresh();
        $customer = $c->getContext('order_customer');
        $this->assertEquals('Rahma', $customer['name']);
        $this->assertEquals('Jl Kawi no 8 Malang', $customer['address']);
        $this->assertEquals($date, $customer['date']);
    }

    public function test_fxi_diterima_sebagai_fix(): void
    {
        $c = $this->newConversation();
        $this->driveToSummary($c);
        $this->bot->handleMessage($c, 'FXI');

        $this->assertEquals(1, Order::count());
    }

    public function test_jawaban_jam_di_langkah_tanggal_diarahkan(): void
    {
        $c = $this->newConversation();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, '1');
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'Budi');
        $this->bot->handleMessage($c, 'Jl. Mawar No 10 Surabaya');
        $this->bot->handleMessage($c, 'Jam 9 pagi');

        $c->refresh();
        $this->assertEquals(3, $c->getContext('form_step'));
        $this->assertNull($c->getContext('order_customer')['date'] ?? null);
    }

    public function test_slot_tanpa_produk_minta_produk_dulu(): void
    {
        $c = WhatsAppConversation::create([
            'phone_number' => '6281000000002',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            'context' => ['delivery_method' => 'self_pickup', 'ongkir' => 0, 'location_done' => true],
        ]);

        $this->bot->handleMessage($c, '1');

        $c->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state);
    }

    public function test_perjalanan_penuh_dari_nol_sampai_order(): void
    {
        Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        $c = WhatsAppConversation::create([
            'phone_number' => '6281000000003',
            'region_id' => null,
            'status' => 'active',
            'current_state' => OrderBotConversationState::WELCOME_SENT->value,
        ]);

        // PESAN -> pilih cabang
        $this->bot->handleMessage($c, 'PESAN');
        $c->refresh();
        $this->assertTrue((bool) $c->getContext('awaiting_branch'));

        // Pilih Denpasar (alfabetis: Denpasar no 1)
        $this->bot->handleMessage($c, '1');
        $c->refresh();
        $this->assertEquals($this->region->id, $c->region_id);
        $this->assertEquals(
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, $c->current_state
        );

        // Kirim lokasi -> langsung pilih produk (katalog)
        $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);
        $c->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state);
        $this->assertTrue((bool) $c->getContext('location_done'));

        // Pilih produk no 1 (Kue Ijo) -> varian
        $this->bot->handleMessage($c, '1');
        $c->refresh();
        $this->assertNotEmpty($c->getContext('pending_options'));

        // Pilih varian 1 (Isi 3)
        $this->bot->handleMessage($c, '1');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart'));
        $this->assertEquals(4, $c->getContext('form_step'));

        // LANJUT -> nama -> alamat -> tanggal
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'Pak Budi');
        $this->bot->handleMessage($c, 'Jl. Raya No 5 Denpasar');
        $this->bot->handleMessage($c, now()->addDays(4)->format('d M Y'));
        $c->refresh();

        // Metode + slot
        $this->bot->handleMessage($c, '3'); // kurir internal -> minta lokasi? pin sudah ada -> slot?
        $c->refresh();
        $state = $c->current_state;
        $this->assertContains($state, [
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value,
            OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
        ]);

        if ($state === OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value) {
            $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);
            $c->refresh();
        }

        $this->bot->handleMessage($c, 'pagi');
        $c->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $c->current_state);

        $this->bot->handleMessage($c, 'FIX');

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertCount(1, $order->items);
        $this->assertEquals('Pak Budi', $order->customer->name);
    }
}
