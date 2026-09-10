<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Models\Category;
use App\Models\Customer;
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

class OrderBotStateTransitionTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected OrderBotService $botService;
    protected WhatsAppConversation $conversation;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.default_region' => 'Denpasar']);

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        CustomerCategory::create(['name' => 'Reseller']);

        // Fake Meta API calls
        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);

        // Create default roles
        Role::create(['name' => 'admin']);
        Role::create(['name' => 'kurir']);

        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        $category = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);
        $product = Product::create([
            'category_id' => $category->id,
            'region_id' => $this->region->id,
            'name' => 'Tumpeng Mini Mix',
            'description' => 'Tumpeng mini',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Paket Mini',
            'price' => 250000,
            'is_active' => true,
        ]);

        $this->botService = app(OrderBotService::class);

        $this->conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);
    }

    public function test_init_to_welcome_sent(): void
    {
        $this->botService->handleMessage($this->conversation, 'Halo');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::WELCOME_SENT->value, $this->conversation->current_state);
    }

    public function test_welcome_sent_to_menu_selection_via_pesan(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::WELCOME_SENT->value]);

        $this->botService->handleMessage($this->conversation, 'Mau pesan');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::MENU_SELECTION->value, $this->conversation->current_state);
    }

    public function test_menu_selection_to_product_browsing_tumpeng(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::MENU_SELECTION->value]);

        $this->botService->handleMessage($this->conversation, 'Tumpeng');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::PRODUCT_BROWSING->value, $this->conversation->current_state);
        $this->assertEquals('Tumpeng', $this->conversation->getContext('selected_category'));
    }

    public function test_product_browsing_to_awaiting_order_form(): void
    {
        $this->conversation->update(['current_state' => OrderBotConversationState::PRODUCT_BROWSING->value]);

        $this->botService->handleMessage($this->conversation, 'Pesan');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_ORDER_FORM->value, $this->conversation->current_state);
    }

    public function test_awaiting_delivery_method_self_pickup(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_METHOD->value,
            'context' => [
                'form_step' => 5,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, '1');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_DELIVERY_SLOT->value, $this->conversation->current_state);
        $this->assertEquals('self_pickup', $this->conversation->getContext('delivery_method'));
        $this->assertEquals(0, $this->conversation->getContext('ongkir'));
    }

    public function test_delivery_slot_to_order_summary(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, '1');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::ORDER_SUMMARY->value, $this->conversation->current_state);
        $this->assertEquals('09:00-11:00', $this->conversation->getContext('delivery_slot'));
    }

    public function test_order_summary_fix_creates_order(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jl. Test No.1',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'FIX');

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseHas('orders', [
            'status' => 'baru',
            'channel' => 'whatsapp',
            'payment_method' => 'qris',
        ]);

        $this->assertDatabaseCount('order_items', 1);

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::AWAITING_PAYMENT_PROOF->value, $this->conversation->current_state);
        $this->assertNotNull($this->conversation->getContext('confirmed_order_id'));
    }

    public function test_order_summary_batal_cancels_order(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'order_form' => ['product_name' => 'Test'],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'BATAL');

        $this->conversation->refresh();
        $this->assertEquals(OrderBotConversationState::MENU_SELECTION->value, $this->conversation->current_state);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_customer_is_created_on_order_confirm(): void
    {
        $this->assertDatabaseCount('customers', 0);

        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi Santoso',
                    'recipient_address' => 'Jl. Test No.1 Denpasar',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'fix');

        $this->assertDatabaseCount('customers', 1);
        $this->assertDatabaseHas('customers', [
            'name' => 'Budi Santoso',
            'phone' => '6281234567890',
            'region_id' => $this->region->id,
        ]);
    }

    public function test_invoice_number_format(): void
    {
        $this->conversation->update([
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_price' => 250000,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Test',
                    'recipient_address' => 'Test Address',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($this->conversation, 'FIX');

        $order = Order::first();
        $this->assertMatchesRegularExpression('/^INV\/\d{4}\/\d{2}\/\d{3}\/\d{3}\/\d{3}$/', $order->invoice_number);
    }
}
