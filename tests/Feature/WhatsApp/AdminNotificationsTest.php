<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Events\WhatsAppMessageReceived;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\AdminNotificationRouterService;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected User $admin;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid_notif']]], 200),
        ]);

        Role::create(['name' => 'admin']);

        $this->region = Region::create(['name' => 'Malang', 'slug' => 'malang']);
        $this->admin = User::create([
            'name' => 'Admin Malang',
            'email' => 'admin.malang@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $this->admin->assignRole('admin');

        $this->customer = Customer::create([
            'name' => 'Budi',
            'phone' => '6282217160075',
            'address' => 'Jalan Langgar',
            'region_id' => $this->region->id,
        ]);
    }

    private function makeOrder(string $channel = 'web', string $status = 'baru'): Order
    {
        return Order::create([
            'customer_id' => $this->customer->id,
            'phone' => $this->customer->phone,
            'address' => $this->customer->address,
            'total_amount' => 150000,
            'payment_method' => 'qris',
            'status' => $status,
            'region_id' => $this->region->id,
            'channel' => $channel,
            'invoice_number' => 'INV-' . Str::random(8),
        ]);
    }

    public function test_notify_new_order_saves_notification_for_admin(): void
    {
        $order = $this->makeOrder('whatsapp');

        app(AdminNotificationRouterService::class)->notifyNewOrder($order->id);

        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'region_id' => $this->region->id,
            'order_id' => $order->id,
            'type' => 'new_order',
            'is_read' => false,
        ]);
    }

    public function test_order_status_update_creates_admin_notification_and_notifies_customer(): void
    {
        $order = $this->makeOrder('whatsapp');

        WhatsAppConversation::create([
            'phone_number' => $this->customer->phone,
            'region_id' => $this->region->id,
            'customer_id' => $this->customer->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
        ]);

        // Simulates admin updating status → OrderObserver fires automatically
        $order->update(['status' => 'dikemas']);

        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'order_id' => $order->id,
            'type' => 'order_status',
            'title' => "Order {$order->invoice_number} → dikemas",
        ]);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'graph.facebook.com')
                && $request['type'] === 'text'
                && $request['to'] === $this->customer->phone
                && $request['text']['body'] === '📦 Pesanan Anda sedang dikemas.';
        });
    }

    public function test_new_order_notification_fires_on_confirm_even_without_payment_proof(): void
    {
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

        $conversation = WhatsAppConversation::create([
            'phone_number' => $this->customer->phone,
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::ORDER_SUMMARY->value,
            'context' => [
                'delivery_method' => 'self_pickup',
                'delivery_slot' => '09:00-11:00',
                'ongkir' => 0,
                'product_quantity' => 1,
                'order_form' => [
                    'product_name' => 'Tumpeng Mini',
                    'recipient_name' => 'Budi',
                    'recipient_address' => 'Jalan Langgar',
                    'delivery_date' => '10 September 2026',
                    'delivery_time' => '10:00',
                ],
            ],
        ]);

        $this->actingAs($this->admin);
        app(OrderBotService::class)->handleMessage($conversation, 'FIX');

        $order = Order::first();

        $this->assertNotNull($order, 'Order harus dibuat saat FIX');
        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'order_id' => $order->id,
            'type' => 'new_order',
            'is_read' => false,
        ]);
    }

    public function test_escalated_incoming_message_creates_notification_for_admin(): void
    {
        $conversation = WhatsAppConversation::create([
            'phone_number' => $this->customer->phone,
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::ESCALATED_TO_HUMAN->value,
        ]);

        $message = WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => 'wamid_incoming_1',
            'sender_type' => 'customer',
            'message_type' => 'text',
            'content' => 'Mau tanya harga kue ulang tahun',
            'status' => 'received',
        ]);

        WhatsAppMessageReceived::dispatch($message, $this->customer->phone, 'Mau tanya harga kue ulang tahun');

        $this->assertDatabaseHas('admin_notifications', [
            'user_id' => $this->admin->id,
            'region_id' => $this->region->id,
            'type' => 'message',
            'title' => 'Pesan masuk (perlu respon)',
            'is_read' => false,
        ]);
    }
}