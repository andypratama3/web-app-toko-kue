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
 * Serangan input tak terduga di tiap langkah order.
 * Prinsip: TIDAK BOLEH crash (500/exception), TIDAK BOLEH order nyasar,
 * TIDAK BOLEH hapus data; bot harus tetap memandu atau menolak sopan.
 */
class OrderBotAdversarialTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $bot;

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
        $ijo = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'ijo', 'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $ijo->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true]);

        $this->bot = app(OrderBotService::class);
    }

    protected function convo(array $context = [], string $state = 'AWAITING_ORDER_FORM'): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => '6281' . random_int(100000000, 999999999),
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::from($state)->value,
            'context' => array_merge(['location_done' => true, 'form_step' => 0], $context),
        ]);
    }

    protected function assertNoOrderAndState($c, string $state): void
    {
        $c->refresh();
        $this->assertEquals($state, $c->current_state);
        $this->assertEquals(0, Order::count());
    }

    public function test_input_aneh_di_langkah_produk(): void
    {
        // Sampah murni: tetap di langkah produk, cart kosong.
        foreach (['', '   ', '%', '_', '%%%', "'; DROP TABLE--", str_repeat('kue ', 60), '😀🍰'] as $i => $input) {
            $c = $this->convo();
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state, "input#$i: $input");
            $this->assertEquals([], $c->getContext('cart') ?? [], "input#$i: $input");
        }
        // Qty ekstrem pada produk VALID: dibatasi + masuk keranjang (tidak crash).
        // '99999x' (5 digit) memang ditolak sebagai nama tak dikenal — wajar.
        foreach (['-5x Kue Ijo', '0x Kue Ijo', '100x Kue Ijo'] as $input) {
            $c = $this->convo();
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            $this->assertCount(1, $c->getContext('cart') ?? [], "input: $input");
        }
        $this->assertEquals(0, Order::count());
    }

    public function test_input_aneh_di_langkah_nama(): void
    {
        // Siapkan cart dulu via API internal: satu produk single-varian.
        // 'x','1' bukan nama (terlalu pendek/angka) -> tetap; 'BATALYON'
        // adalah nama sah -> lanjut; 'PESAN' persis = restart TAPI keranjang
        // dipertahankan (anti-putus) + lokasi diverifikasi ulang.
        foreach ([['x', 1], ['1', 1], ['BATALYON', 2]] as $i => [$input, $expectedStep]) {
            $c = $this->convo();
            $this->bot->handleMessage($c, 'Kue Ijo'); // masuk keranjang (1 varian)
            $this->bot->handleMessage($c, 'LANJUT');
            $c->refresh();
            $this->assertEquals(1, $c->getContext('form_step'), "setup#$i");
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            $this->assertEquals($expectedStep, $c->getContext('form_step'), "input#$i: $input");
        }
        // PESAN persis: restart, keranjang TETAP ADA, lokasi dicek ulang.
        $c = $this->convo();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'PESAN');
        $c->refresh();
        $this->assertCount(1, $c->getContext('cart') ?? []);
        $this->assertEquals('cart', $c->getContext('after_location'));
        // 'Jl. Mawar No 10' sebagai NAMA juga sah (bebas, tanggung jawab user).
        $c = $this->convo();
        $this->bot->handleMessage($c, 'Kue Ijo');
        $this->bot->handleMessage($c, 'LANJUT');
        $this->bot->handleMessage($c, 'Jl. Mawar No 10');
        $c->refresh();
        $this->assertEquals(2, $c->getContext('form_step'));
        $this->assertEquals(0, Order::count());
    }

    public function test_input_aneh_di_langkah_alamat(): void
    {
        // 'a'/'Jl'/'MENU' terlalu pendek -> tetap; alamat ber-kata MENU diterima.
        foreach ([['a', 2], ['Jl', 2], ['MENU', 2], ['Jl. MENU Utama No 1', 3]] as $i => [$input, $expectedStep]) {
            $c = $this->convo();
            $this->bot->handleMessage($c, 'Kue Ijo');
            $this->bot->handleMessage($c, 'LANJUT');
            $this->bot->handleMessage($c, 'Budi');
            $c->refresh();
            $this->assertEquals(2, $c->getContext('form_step'), "setup#$i");
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            if ($input === 'BATAL') {
                // BATAL global tetap bisa keluar.
                $this->assertEquals(OrderBotConversationState::MENU_SELECTION->value, $c->current_state, "input#$i");
            } else {
                $this->assertEquals($expectedStep, $c->getContext('form_step'), "input#$i: $input");
            }
        }
        $this->assertEquals(0, Order::count());
    }

    public function test_input_aneh_di_langkah_tanggal(): void
    {
        foreach (['asdf', 'kemarin', '32/13/2026', '25', 'Desember', '10:00', 'pagi'] as $i => $input) {
            $c = $this->convo();
            $this->bot->handleMessage($c, 'Kue Ijo');
            $this->bot->handleMessage($c, 'LANJUT');
            $this->bot->handleMessage($c, 'Budi');
            $this->bot->handleMessage($c, 'Jl. Mawar No 10 Surabaya');
            $c->refresh();
            $this->assertEquals(3, $c->getContext('form_step'), "setup#$i");
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            $this->assertEquals(3, $c->getContext('form_step'), "input#$i: $input (tanggal harus ditolak)");
            $this->assertNull($c->getContext('order_customer')['date'] ?? null, "input#$i");
        }
        $this->assertEquals(0, Order::count());
    }

    public function test_tanggal_natural_diterima(): void
    {
        foreach (['besok', 'lusa', 'hari ini', now()->addDays(9)->format('d M Y'), now()->addDays(9)->format('d/m/Y')] as $input) {
            $c = $this->convo();
            $this->bot->handleMessage($c, 'Kue Ijo');
            $this->bot->handleMessage($c, 'LANJUT');
            $this->bot->handleMessage($c, 'Budi');
            $this->bot->handleMessage($c, 'Jl. Mawar No 10 Surabaya');
            $this->bot->handleMessage($c, $input);
            $c->refresh();
            $this->assertNotEquals(3, $c->getContext('form_step'), "input: $input (tanggal harus diterima)");
        }
        $this->assertEquals(0, Order::count());
    }

    public function test_fix_tanpa_data_tidak_bikin_order(): void
    {
        foreach (['ORDER_SUMMARY', 'AWAITING_ORDER_FORM', 'AWAITING_DELIVERY_METHOD'] as $state) {
            $c = $this->convo([], $state);
            $this->bot->handleMessage($c, 'FIX');
            $this->assertEquals(0, Order::count(), "state: $state");
        }
    }

    public function test_gambar_di_tengah_form_tidak_crash(): void
    {
        $c = $this->convo();
        $this->bot->handleMessage($c, '[Gambar]', ['media_id' => 'm1', 'media_path' => 'payment_proofs/x.jpg']);
        $c->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $c->current_state);
        $this->assertEquals(0, Order::count());
    }
}
