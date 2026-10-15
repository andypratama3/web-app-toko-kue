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
 * Regresi bug "FIX mental ke pilih metode": jalur lokasi-dulu
 * (cabang → lokasi → metode → slot) tidak pernah menanyakan produk,
 * sehingga ringkasan pincang & konfirmasi memantul selamanya.
 */
class OrderBotLocationFirstFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $bot;
    protected Product $ijo;

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
        $this->ijo = Product::create([
            'category_id' => $produk->id, 'region_id' => null,
            'name' => 'Kue Ijo', 'description' => 'ijo', 'tag' => 'Ala Carte', 'is_active' => true,
        ]);
        ProductVariant::create(['product_id' => $this->ijo->id, 'name' => 'Isi 3', 'price' => 9000, 'is_active' => true]);

        $this->bot = app(OrderBotService::class);
    }

    protected function makeConversation(array $context): WhatsAppConversation
    {
        return WhatsAppConversation::create([
            'phone_number' => '6281000000001',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            'context' => $context,
        ]);
    }

    public function test_slot_tanpa_produk_minta_produk_dulu(): void
    {
        $conversation = $this->makeConversation(['delivery_method' => 'self_pickup', 'ongkir' => 0]);

        $this->bot->handleMessage($conversation, '1');

        $conversation->refresh();
        $this->assertEquals(
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            $conversation->current_state
        );
        $this->assertEquals(0, $conversation->getContext('form_step'));
    }

    public function test_form_gabungan_dengan_metode_slot_langsung_ringkasan(): void
    {
        $conversation = $this->makeConversation([
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'ongkir' => 0,
        ]);
        $conversation->update(['current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value]);

        // Langkah produk (satu varian → langsung form gabungan)
        $this->bot->handleMessage($conversation, 'Kue Ijo');
        // Langkah data penerima
        $this->bot->handleMessage($conversation, 'Shinta | Jl. Mawar 10 | 20 Okt 2026');

        $conversation->refresh();
        $this->assertEquals(
            OrderBotConversationState::ORDER_SUMMARY->value,
            $conversation->current_state
        );
    }

    public function test_fix_dengan_produk_lengkap_membuat_order(): void
    {
        $conversation = $this->makeConversation([
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'ongkir' => 0,
        ]);
        $conversation->update(['current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value]);

        $this->bot->handleMessage($conversation, 'Kue Ijo');
        $this->bot->handleMessage($conversation, 'Shinta | Jl. Mawar 10 | 20 Okt 2026');
        $this->bot->handleMessage($conversation, 'FIX');

        $this->assertEquals(1, Order::count());
        $order = Order::first();
        $this->assertEquals(9000, $order->total_amount);
        $this->assertEquals('Kue Ijo', $order->items->first()->product_name);
    }

    public function test_fix_ganda_tidak_membuat_order_ganda(): void
    {
        $conversation = $this->makeConversation([
            'delivery_method' => 'self_pickup',
            'delivery_slot' => '09:00-11:00',
            'ongkir' => 0,
        ]);
        $conversation->update(['current_state' => OrderBotConversationState::AWAITING_ORDER_FORM->value]);

        $this->bot->handleMessage($conversation, 'Kue Ijo');
        $this->bot->handleMessage($conversation, 'Shinta | Jl. Mawar 10 | 20 Okt 2026');
        $this->bot->handleMessage($conversation, 'FIX');

        // Simulasi double-tap / retry: kembali ke summary lalu FIX lagi.
        $conversation->update(['current_state' => OrderBotConversationState::ORDER_SUMMARY->value]);
        $this->bot->handleMessage($conversation, 'FIX');

        $this->assertEquals(1, Order::count());
    }
}
