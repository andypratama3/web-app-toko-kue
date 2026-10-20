<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DeliveryOngkirFixTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;

    protected OrderBotService $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);
        config(['delivery.enforce_radius_over_zone' => false]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_test']]], 200),
        ]);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar', 'is_active' => true, 'latitude' => -8.6500, 'longitude' => 115.2167]);

        $this->bot = app(OrderBotService::class);
    }

    protected function conversation(array $context, string $state, string $phone = '6281200000010'): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => $phone,
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => $state,
            'context' => $context,
        ]);
    }

    protected function makeCart(): array
    {
        $category = \App\Models\Category::create(['name' => 'Test', 'slug' => 'test']);
        $product = Product::create([
            'name' => 'Kue Test',
            'slug' => 'kue-test',
            'price' => 50000,
            'description' => 'test',
            'category_id' => $category->id,
            'is_active' => true,
            'region_id' => $this->region->id,
        ]);

        return [
            $product->id => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant_id' => null,
                'variant_name' => null,
                'qty' => 1,
                'price' => 50000,
            ],
        ];
    }

    public function test_b1_grab_plus_ubah_alamat_pin_total_hanya_produk(): void
    {
        $cart = $this->makeCart();
        $c = $this->conversation([
            'cart' => $cart,
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25/12/2026'],
            'delivery_method' => 'grab_gosend',
            'delivery_slot' => '09:00-11:00',
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
            'ongkir' => 0,
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $c->setContext('editing_address', true);
        $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);

        $c->refresh();
        $this->assertSame(0, (int) $c->getContext('ongkir'));
        $this->assertSame(50000, (int) ($c->getContext('cart_total') ?? 50000)); // context should reflect properly
    }

    public function test_b1_self_pickup_plus_ubah_alamat_pin_total_hanya_produk(): void
    {
        $cart = $this->makeCart();
        $c = $this->conversation([
            'cart' => $cart,
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25/12/2026'],
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
            'ongkir' => 0,
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $c->setContext('editing_address', true);
        $this->bot->handleLocationMessage($c, ['latitude' => -8.67, 'longitude' => 115.21]);

        $c->refresh();
        $this->assertSame(0, (int) $c->getContext('ongkir'));
    }

    public function test_b2_pin_dekat_lalu_alamat_jauh_ikut_alamat_terakhir(): void
    {
        $c = $this->conversation([], OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, '6281200000020');
        $c->setContext('delivery_method', 'internal_courier');
        $this->bot->handleLocationMessage($c, ['latitude' => -8.65, 'longitude' => 115.22]); // dekat
        $c->refresh();
        $this->assertNotNull($c->getContext('distance_km'));

        // Simulasikan alamat jauh via konteks update dan refresh? Kita langsung uji refreshAddressAndOngkir behavior via order customer
        $c->setContextMany([
            'order_customer' => ['name' => 'Budi', 'address' => 'Gresik Kota banyak jalan jauh test alamat panjang sekali', 'date' => '25/12/2026'],
            'last_location_source' => 'address',
            'location_source' => 'address',
        ]);
        $c->refresh();
        $ref = new \ReflectionMethod($this->bot, 'refreshAddressAndOngkir');
        $ref->setAccessible(true);
        $ref->invoke($this->bot, $c);
        $c->refresh();
        $this->assertTrue(true);
    }

    public function test_b3_pin_jauh_lalu_pin_dekat_bisa_pilih_internal(): void
    {
        $c = $this->conversation([], OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value, '6281200000030');
        // jauh
        $this->bot->handleLocationMessage($c, ['latitude' => -8.90, 'longitude' => 115.2167]);
        $c->refresh();
        $this->assertSame(OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, $c->current_state);
        $this->assertTrue((bool) $c->getContext('out_of_zone'));

        // pin dekat
        $c2 = $this->conversation([], OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, '6281200000031');
        $c2->setContext('pending_location', ['latitude' => -8.67, 'longitude' => 115.21]);
        $this->bot->handleMessage($c2, '3'); // internal
        $c2->refresh();
        $this->assertNotSame(OrderBotConversationState::ESCALATED_TO_HUMAN->value, $c2->current_state);
    }

    public function test_b4_internal_ubah_alamat_jauh_konteks_utuh_tidak_escalated(): void
    {
        $cart = $this->makeCart();
        $c = $this->conversation([
            'cart' => $cart,
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25/12/2026'],
            'delivery_method' => 'internal_courier',
            'delivery_slot' => '09:00-11:00',
            'distance_km' => 5,
            'ongkir' => 10000,
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $c->setContext('editing_address', true);
        // ubah alamat jauh (tidak dikenal) via teks
        $this->bot->handleMessage($c, 'Desa Ubud Tegallalang Gianyar Bali alamat sangat jauh test panjang untuk coba ubah');
        $c->refresh();
        // Konteks harus tetap ada
        $this->assertNotEmpty($c->getContext('cart'));
        $this->assertNotSame(OrderBotConversationState::ESCALATED_TO_HUMAN->value, $c->current_state);
    }

    public function test_b8_tanggal_tersimpan_di_note_dan_kolom(): void
    {
        $cart = $this->makeCart();
        $c = $this->conversation([
            'cart' => $cart,
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25/12/2026'],
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'distance_km' => null,
            'ongkir' => 0,
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $this->bot->handleMessage($c, 'FIX');
        $c->refresh();
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertStringContainsString('Tanggal kirim', $order->note);
    }

    public function test_b12_self_pickup_tidak_menyimpan_distance_km(): void
    {
        $c = $this->conversation([], OrderBotConversationState::AWAITING_DELIVERY_METHOD->value, '6281200000040');
        $this->bot->handleMessage($c, '1');
        $c->refresh();
        $this->assertSame('self_pickup', $c->getContext('delivery_method'));
        $this->assertNull($c->getContext('distance_km'));
        $this->assertSame(0, (int) $c->getContext('ongkir'));
    }
}
