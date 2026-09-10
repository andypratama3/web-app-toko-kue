<?php

namespace Tests\Feature\WhatsApp;

use App\Enums\OrderBotConversationState;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Services\WhatsApp\IncomingMediaHandler;
use App\Services\WhatsApp\OrderBotService;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class MultiBranchWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.verify_token' => 'test-token']);
        config(['services.whatsapp.webhook_secret' => '']);
        config(['services.whatsapp.default_region' => 'Denpasar']);
        config(['services.whatsapp.phone_number_id' => '111111111111111']);

        Region::create(['name' => 'Denpasar', 'slug' => 'denpasar']);
        Region::create([
            'name' => 'Surabaya',
            'slug' => 'surabaya',
            'meta_phone_number_id' => '222222222222222',
        ]);

        Http::fake([
            'graph.facebook.com/*' => Http::response(['messages' => ['id' => 'wamid_test']], 200),
            'lookaside.fbsbx.com/*' => Http::response('fake-image', 200, ['Content-Type' => 'image/jpeg']),
        ]);
    }

    public function test_webhook_from_registered_number_routes_new_conversation_to_that_region(): void
    {
        $surabaya = Region::where('slug', 'surabaya')->first();
        $payload = $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_mb_001', '222222222222222');

        $job = new ProcessWhatsAppWebhookJob($payload, '222222222222222');
        $job->handle(
            app(WhatsappMetaService::class),
            app(OrderBotService::class),
            app(IncomingMediaHandler::class)
        );

        $conversation = WhatsAppConversation::first();
        $this->assertNotNull($conversation);
        $this->assertEquals($surabaya->id, $conversation->region_id);

        Http::assertSent(fn($request) => str_contains($request->url(), '/222222222222222/messages'));
    }

    public function test_webhook_from_unregistered_number_is_rejected(): void
    {
        Queue::fake();

        $payload = $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_mb_002', '999999999999999');

        $response = $this->postJson('/api/webhook/meta', $payload);

        $response->assertStatus(403);
        Queue::assertNotPushed(ProcessWhatsAppWebhookJob::class);
    }

    public function test_webhook_without_region_binding_falls_back_to_default_region(): void
    {
        config(['services.whatsapp.phone_number_id' => '111111111111111']);

        $denpasar = Region::where('slug', 'denpasar')->first();
        $payload = $this->buildWebhookPayload('6281234567890', 'Halo', 'msg_mb_003', '111111111111111');

        $job = new ProcessWhatsAppWebhookJob($payload, '111111111111111');
        $job->handle(
            app(WhatsappMetaService::class),
            app(OrderBotService::class),
            app(IncomingMediaHandler::class)
        );

        $conversation = WhatsAppConversation::first();
        $this->assertEquals($denpasar->id, $conversation->region_id);
    }

    public function test_outbound_send_uses_conversation_region_number(): void
    {
        $surabaya = Region::where('slug', 'surabaya')->first();
        WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $surabaya->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        app(WhatsappMetaService::class)->sendText('6281234567890', 'Halo dari Surabaya');

        Http::assertSent(fn($request) => str_contains($request->url(), '/222222222222222/messages'));
    }

    public function test_outbound_send_falls_back_to_config_number_when_region_has_no_number(): void
    {
        $denpasar = Region::where('slug', 'denpasar')->first();
        WhatsAppConversation::create([
            'phone_number' => '6281234567890',
            'region_id' => $denpasar->id,
            'status' => 'active',
            'current_state' => OrderBotConversationState::INIT->value,
        ]);

        app(WhatsappMetaService::class)->sendText('6281234567890', 'Halo dari Denpasar');

        Http::assertSent(fn($request) => str_contains($request->url(), '/111111111111111/messages'));
    }

    protected function buildWebhookPayload(string $phone, string $text, string $messageId, string $phoneNumberId): array
    {
        return [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456',
                    'changes' => [
                        [
                            'value' => [
                                'metadata' => [
                                    'display_phone_number' => '62851xxxxx',
                                    'phone_number_id' => $phoneNumberId,
                                ],
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