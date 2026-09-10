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

class CustomerQuotaTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected CustomerCategory $resellerCategory;
    protected Customer $customer;
    protected OrderBotService $botService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->region = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        $this->resellerCategory = CustomerCategory::create(['name' => 'Reseller']);

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
            'name' => 'Tumpeng Mini',
            'description' => 'Tumpeng',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Paket',
            'price' => 250000,
            'is_active' => true,
        ]);

        $this->customer = Customer::create([
            'name' => 'Reseller Test',
            'phone' => '6281234567890',
            'address' => 'Test Address',
            'region_id' => $this->region->id,
            'customer_category_id' => $this->resellerCategory->id,
        ]);

        $this->botService = app(OrderBotService::class);
    }

    public function test_reseller_can_order_under_limit(): void
    {
        // Create 6 active orders (under limit of 7)
        for ($i = 0; $i < 6; $i++) {
            Order::create([
                'customer_id' => $this->customer->id,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'total_amount' => 250000,
                'payment_method' => 'qris',
                'status' => 'baru',
                'region_id' => $this->region->id,
                'channel' => 'web',
            ]);
        }

        $conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
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
                    'delivery_date' => '10 Sep',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($conversation, 'FIX');

        // Should succeed (7th order)
        $this->assertDatabaseCount('orders', 7);
    }

    public function test_reseller_cannot_order_at_limit(): void
    {
        // Create 7 active orders (at limit)
        for ($i = 0; $i < 7; $i++) {
            Order::create([
                'customer_id' => $this->customer->id,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'total_amount' => 250000,
                'payment_method' => 'qris',
                'status' => 'baru',
                'region_id' => $this->region->id,
                'channel' => 'web',
            ]);
        }

        $conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
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
                    'delivery_date' => '10 Sep',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($conversation, 'FIX');

        // Should fail (8th order blocked)
        $this->assertDatabaseCount('orders', 7);
        $this->assertSame(OrderBotConversationState::ORDER_SUMMARY->value, $conversation->fresh()->current_state);
    }

    public function test_completed_orders_not_counted_in_quota(): void
    {
        // Create 7 orders but 3 are completed
        for ($i = 0; $i < 7; $i++) {
            Order::create([
                'customer_id' => $this->customer->id,
                'phone' => $this->customer->phone,
                'address' => $this->customer->address,
                'total_amount' => 250000,
                'payment_method' => 'qris',
                'status' => $i < 3 ? 'selesai' : 'baru',
                'region_id' => $this->region->id,
                'channel' => 'web',
            ]);
        }

        $conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
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
                    'delivery_date' => '10 Sep',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->botService->handleMessage($conversation, 'FIX');

        // Should succeed (only 4 active orders, under limit of 7)
        $this->assertDatabaseCount('orders', 8);
    }
}
