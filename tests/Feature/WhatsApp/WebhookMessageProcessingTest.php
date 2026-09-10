<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Models\User;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WebhookMessageProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected Region $region;
    protected CustomerCategory $resellerCategory;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.verify_token' => 'test-token']);
        config(['services.whatsapp.webhook_secret' => '']);
        config(['services.whatsapp.default_region' => 'Denpasar']);

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

        // Create admin for notifications
        $admin = User::create([
            'name' => 'Admin Denpasar',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'region_id' => $this->region->id,
        ]);
        $admin->assignRole('admin');

        // Create products
        $category = Category::create(['name' => 'Tumpeng', 'slug' => 'tumpeng']);
        $product = Product::create([
            'category_id' => $category->id,
            'region_id' => $this->region->id,
            'name' => 'Tumpeng Mini Mix',
            'description' => 'Tumpeng mini untuk acara',
            'is_active' => true,
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Paket Mini',
            'price' => 250000,
            'is_active' => true,
        ]);
    }

    public function test_incoming_message_dispatches_job(): void
    {
        Queue::fake();

        $payload = $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_001');

        $response = $this->postJson('/api/webhook/meta', $payload);

        $response->assertStatus(200);
        Queue::assertPushed(ProcessWhatsAppWebhookJob::class);
    }

    public function test_job_creates_conversation_on_first_message(): void
    {
        $this->assertDatabaseCount('whatsapp_conversations', 0);

        $job = new ProcessWhatsAppWebhookJob(
            $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_001')
        );
        $job->handle(
            app(\App\Services\WhatsApp\WhatsappMetaService::class),
            app(\App\Services\WhatsApp\OrderBotService::class),
            app(\App\Services\WhatsApp\IncomingMediaHandler::class)
        );

        $this->assertDatabaseCount('whatsapp_conversations', 1);

        $conversation = WhatsAppConversation::first();
        $this->assertEquals('6281234567890', $conversation->phone_number);
        $this->assertEquals($this->region->id, $conversation->region_id);
        $this->assertEquals(OrderBotConversationState::WELCOME_SENT->value, $conversation->current_state);
    }

    public function test_job_saves_incoming_message(): void
    {
        $job = new ProcessWhatsAppWebhookJob(
            $this->buildWebhookPayload('6281234567890', 'Test pesan', 'msg_002')
        );
        $job->handle(
            app(\App\Services\WhatsApp\WhatsappMetaService::class),
            app(\App\Services\WhatsApp\OrderBotService::class),
            app(\App\Services\WhatsApp\IncomingMediaHandler::class)
        );

        $this->assertDatabaseCount('whatsapp_messages', 2);

        $message = WhatsAppMessage::where('sender_type', 'customer')->first();
        $this->assertEquals('msg_002', $message->whatsapp_message_id);
        $this->assertEquals('customer', $message->sender_type);
        $this->assertEquals('text', $message->message_type);
        $this->assertEquals('Test pesan', $message->content);
    }

    public function test_duplicate_message_is_skipped(): void
    {
        // First message
        $job = new ProcessWhatsAppWebhookJob(
            $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_dup_001')
        );
        $job->handle(
            app(\App\Services\WhatsApp\WhatsappMetaService::class),
            app(\App\Services\WhatsApp\OrderBotService::class),
            app(\App\Services\WhatsApp\IncomingMediaHandler::class)
        );

        $this->assertDatabaseCount('whatsapp_messages', 2);

        // Duplicate message (Meta retry)
        $job2 = new ProcessWhatsAppWebhookJob(
            $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_dup_001')
        );
        $job2->handle(
            app(\App\Services\WhatsApp\WhatsappMetaService::class),
            app(\App\Services\WhatsApp\OrderBotService::class),
            app(\App\Services\WhatsApp\IncomingMediaHandler::class)
        );

        // No extra customer message (dedup) and no duplicate bot reply
        $this->assertDatabaseCount('whatsapp_messages', 2);
        $this->assertSame(1, WhatsAppMessage::where('sender_type', 'customer')->count());
    }

    public function test_status_update_is_processed(): void
    {
        // Create a conversation first
        $conversation = WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $this->region->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        // Create an outgoing message
        $message = WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => 'outgoing_msg_001',
            'sender_type' => 'bot',
            'message_type' => 'text',
            'content' => 'Test',
            'status' => 'sent',
        ]);

        // Simulate status update webhook
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456',
                    'changes' => [
                        [
                            'value' => [
                                'statuses' => [
                                    [
                                        'id' => 'outgoing_msg_001',
                                        'status' => 'delivered',
                                        'recipient_id' => '6281234567890',
                                        'timestamp' => (string) now()->timestamp,
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $job = new ProcessWhatsAppWebhookJob($payload);
        $job->handle(
            app(\App\Services\WhatsApp\WhatsappMetaService::class),
            app(\App\Services\WhatsApp\OrderBotService::class),
            app(\App\Services\WhatsApp\IncomingMediaHandler::class)
        );

        $message->refresh();
        $this->assertEquals('delivered', $message->status);

        $this->assertDatabaseHas('whatsapp_message_statuses', [
            'message_id' => 'outgoing_msg_001',
            'status' => 'delivered',
        ]);
    }

    protected function buildWebhookPayload(string $phone, string $text, string $messageId): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456',
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'from' => $phone,
                                        'id' => $messageId,
                                        'timestamp' => (string) now()->timestamp,
                                        'type' => 'text',
                                        'text' => ['body' => $text],
                                    ],
                                ],
                                'contacts' => [
                                    [
                                        'wa_id' => $phone,
                                        'profile' => ['name' => 'Test User'],
                                    ],
                                ],
                            ],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];
    }
}
