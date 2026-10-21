<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\DeliveryZone;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\DeliveryQuoteService;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Regression test untuk bug "ongkir diam-diam jadi Rp 0" saat lokasi pin GPS
 * dan alamat ketik user berbeda jauh, serta ongkir Rp 0 saat konfirmasi
 * di luar jangkauan kurir internal.
 */
class DeliveryQuoteConflictTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;

    protected OrderBotService $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);
        config(['delivery.enforce_radius_over_zone' => false]);
        config(['delivery.conflict_threshold_km' => 3]);
        config(['delivery.max_radius_km' => 14]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_test']]], 200),
        ]);

        $this->region = Region::create([
            'name' => 'Denpasar', 'slug' => 'denpasar', 'is_active' => true,
            'latitude' => -8.6500, 'longitude' => 115.2167,
        ]);

        // Zona dekat (5km, Rp10.000) dan zona jauh (12km, Rp15.000).
        DeliveryZone::create([
            'region_id' => $this->region->id,
            'area_name' => 'Sekitar Toko',
            'landmark_keyword' => 'sekitar tokosaya',
            'distance_km' => 5,
            'ongkir' => 10000,
            'is_active' => true,
        ]);
        DeliveryZone::create([
            'region_id' => $this->region->id,
            'area_name' => 'Sanan',
            'landmark_keyword' => 'sanan',
            'distance_km' => 12,
            'ongkir' => 15000,
            'is_active' => true,
        ]);

        $this->bot = app(OrderBotService::class);
    }

    protected function conversation(array $context, string $state, string $phone = '6281200000100'): WhatsAppConversation
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
        $category = Category::create(['name' => 'Test Konflik', 'slug' => 'test-konflik']);
        $product = Product::create([
            'name' => 'Kue Konflik',
            'slug' => 'kue-konflik',
            'price' => 50000,
            'description' => 'test',
            'category_id' => $category->id,
            'is_active' => true,
            'region_id' => $this->region->id,
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Varian Default',
            'price' => 50000,
            'is_active' => true,
        ]);

        return [
            $product->id => [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'variant_id' => $variant->id,
                'variant_name' => $variant->name,
                'qty' => 1,
                'price' => 50000,
            ],
        ];
    }

    /** Lat -8.61 ≈ 4,4km dari toko; alamat "Sanan" ≈ 12km → beda jauh. */
    protected const PIN_LAT = -8.61;

    protected const PIN_LNG = 115.2167;

    // ------------------------------------------------------------------
    // Unit: DeliveryQuoteService
    // ------------------------------------------------------------------

    public function test_konflik_pin_alamat_tidak_pernah_menghasilkan_ongkir_nol(): void
    {
        $quote = app(DeliveryQuoteService::class)->quote([
            'region_id' => $this->region->id,
            'delivery_method' => 'internal_courier',
            'location_source' => 'address',
            'last_location_source' => 'address',
            'pin_lat' => self::PIN_LAT,
            'pin_lng' => self::PIN_LNG,
            'address_text' => 'Jl. Sanan No 3 Denpasar Barat',
        ]);

        $this->assertTrue($quote['needs_conflict_resolution']);
        $this->assertSame('address', $quote['location_source']);
        $this->assertSame(12.0, (float) $quote['distance_km']);
        $this->assertSame(15000, (int) $quote['ongkir']);
        $this->assertNotSame(0, (int) $quote['ongkir'], 'Ongkir konflik tak boleh 0');
    }

    public function test_konflik_pin_terakhir_menang(): void
    {
        $quote = app(DeliveryQuoteService::class)->quote([
            'region_id' => $this->region->id,
            'delivery_method' => 'internal_courier',
            'location_source' => 'pin',
            'last_location_source' => 'pin',
            'pin_lat' => self::PIN_LAT,
            'pin_lng' => self::PIN_LNG,
            'address_text' => 'Jl. Sanan No 3 Denpasar Barat',
        ]);

        $this->assertTrue($quote['needs_conflict_resolution']);
        $this->assertSame('pin', $quote['location_source']);
        $this->assertSame(10000, (int) $quote['ongkir']);
        $this->assertGreaterThan(0, (int) $quote['ongkir']);
    }

    public function test_jarak_tak_diketahui_menandai_needs_pin(): void
    {
        $quote = app(DeliveryQuoteService::class)->quote([
            'region_id' => $this->region->id,
            'delivery_method' => 'internal_courier',
            'address_text' => ' someplace yang tidak ada keywordnya sama sekali',
        ]);

        $this->assertTrue($quote['needs_pin']);
        $this->assertNull($quote['distance_km']);
    }

    // ------------------------------------------------------------------
    // Flow: ubah alamat dari ringkasan pesanan
    // ------------------------------------------------------------------

    public function test_ubah_alamat_konflik_ongkir_bukan_nol_dan_user_diberitahu(): void
    {
        $c = $this->conversation([
            'cart' => $this->makeCart(),
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Des 2026'],
            'delivery_method' => 'internal_courier',
            'delivery_slot' => '09:00-11:00',
            'distance_km' => 4.44,
            'ongkir' => 10000,
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
            'delivery_lat' => self::PIN_LAT,
            'delivery_lng' => self::PIN_LNG,
            'location_source' => 'pin',
            'last_location_source' => 'pin',
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $this->bot->handleMessage($c, 'UBAH ALAMAT');
        $this->bot->handleMessage($c, 'Jl. Sanan No 3 Denpasar Barat');

        $c->refresh();
        $this->assertSame(15000, (int) $c->getContext('ongkir'), 'Ongkir harus mengikuti sumber terakhir (alamat)');
        $this->assertSame(12.0, (float) $c->getContext('distance_km'));
        $this->assertSame('address', $c->getContext('location_source'));

        Http::assertSent(function ($request) {
            $body = (string) $request->body();

            return str_contains($body, 'berbeda jauh');
        });
    }

    public function test_fix_setelah_konflik_menyimpan_ongkir_yang_benar(): void
    {
        $c = $this->conversation([
            'cart' => $this->makeCart(),
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Des 2026'],
            'delivery_method' => 'internal_courier',
            'delivery_slot' => '09:00-11:00',
            'distance_km' => 4.44,
            'ongkir' => 10000,
            'delivery_address' => 'Jl. Mawar No 10 Denpasar',
            'delivery_lat' => self::PIN_LAT,
            'delivery_lng' => self::PIN_LNG,
            'location_source' => 'pin',
            'last_location_source' => 'pin',
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        $this->bot->handleMessage($c, 'UBAH ALAMAT');
        $this->bot->handleMessage($c, 'Jl. Sanan No 3 Denpasar Barat');
        $this->bot->handleMessage($c, 'FIX');

        $c->refresh();
        $order = Order::first();
        $this->assertNotNull($order, 'Order harus terbentuk');
        $this->assertSame(15000, (int) $order->shipping_fee, 'Ongkir order tak boleh 0');
        $this->assertSame(12.0, (float) $order->distance_km);
        $this->assertSame(65000.0, (float) $order->total_amount);
    }

    public function test_form_alamat_terakhir_menang_atas_pin_lama(): void
    {
        // Alamat dari form adalah input lokasi terakhir → ongkir ikut alamat.
        $c = $this->conversation([
            'delivery_method' => 'internal_courier',
            'delivery_slot' => '09:00-11:00',
            'delivery_lat' => self::PIN_LAT,
            'delivery_lng' => self::PIN_LNG,
            'location_source' => 'pin',
            'last_location_source' => 'pin',
            'location_done' => true,
            'form_step' => 1,
        ], OrderBotConversationState::AWAITING_ORDER_FORM->value, '6281200000101');

        $this->bot->handleMessage($c, 'Budi | Jl. Sanan No 3 Denpasar Barat | 25 Des 2026');

        $c->refresh();
        $this->assertSame(15000, (int) $c->getContext('ongkir'));
        $this->assertSame(12.0, (float) $c->getContext('distance_km'));
        $this->assertSame('address', $c->getContext('location_source'));
    }

    // ------------------------------------------------------------------
    // Flow: konfirmasi di luar jangkauan kurir internal
    // ------------------------------------------------------------------

    public function test_konfirmasi_di_luar_jangkauan_tidak_membuat_order_gratis(): void
    {
        $c = $this->conversation([
            'cart' => $this->makeCart(),
            'order_customer' => ['name' => 'Budi', 'address' => 'Gresik Kota', 'date' => '25 Des 2026'],
            'delivery_method' => 'internal_courier',
            'delivery_slot' => '09:00-11:00',
            'distance_km' => 4.44,
            'ongkir' => 10000,
            'delivery_address' => 'Gresik Kota',
            'delivery_lat' => -8.65,
            'delivery_lng' => 115.2167,
            'location_source' => 'pin',
            'last_location_source' => 'pin',
        ], OrderBotConversationState::ORDER_SUMMARY->value);

        // Jarak jauh (>14km) baru ketahuan saat konfirmasi.
        $c->setContextMany([
            'delivery_lat' => -8.85,
            'delivery_lng' => 115.2167,
        ]);
        $c->refresh();

        $this->bot->handleMessage($c, 'FIX');

        $c->refresh();
        $this->assertSame(0, Order::count(), 'Order tak boleh terbentuk dengan ongkir tak terhitung');
        $this->assertTrue((bool) $c->getContext('out_of_zone'));
    }

    // ------------------------------------------------------------------
    // Tanggal bahasa Indonesia
    // ------------------------------------------------------------------

    public function test_tanggal_bahasa_indonesia_tersimpan_benar(): void
    {
        $c = $this->conversation([
            'cart' => $this->makeCart(),
            'order_customer' => ['name' => 'Budi', 'address' => 'Jl. Mawar No 10 Denpasar', 'date' => '25 Des 2026'],
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
        $this->assertSame('2026-12-25', $order->delivery_date->toDateString());
    }
}
