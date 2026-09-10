<?php

namespace Tests\Feature\WhatsApp;

use App\Models\CustomerCategory;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.whatsapp.verify_token' => 'test-verify-token-123']);
        config(['services.whatsapp.webhook_secret' => '']);
    }

    public function test_webhook_verification_with_valid_token(): void
    {
        $response = $this->getJson('/api/webhook/meta?hub_mode=subscribe&hub_verify_token=test-verify-token-123&hub_challenge=CHALLENGE_ACCEPTED');

        $response->assertStatus(200);
        $response->assertContent('CHALLENGE_ACCEPTED');
    }

    public function test_webhook_verification_with_invalid_token(): void
    {
        $response = $this->getJson('/api/webhook/meta?hub_mode=subscribe&hub_verify_token=wrong-token&hub_challenge=CHALLENGE_ACCEPTED');

        $response->assertStatus(403);
    }

    public function test_webhook_verification_versioned_endpoint(): void
    {
        $response = $this->getJson('/api/v1/webhook/whatsapp?hub_mode=subscribe&hub_verify_token=test-verify-token-123&hub_challenge=TEST_CHALLENGE');

        $response->assertStatus(200);
        $response->assertContent('TEST_CHALLENGE');
    }

    public function test_webhook_verification_legacy_meta_url_endpoint(): void
    {
        $response = $this->getJson('/api/webhook/whatsapp/meta?hub_mode=subscribe&hub_verify_token=test-verify-token-123&hub_challenge=LEGACY_CHALLENGE');

        $response->assertStatus(200);
        $response->assertContent('LEGACY_CHALLENGE');
    }

    public function test_webhook_post_returns_ok(): void
    {
        $payload = [
            'object' => 'whatsapp_business_account',
            'entry' => [
                [
                    'id' => '123456',
                    'changes' => [
                        [
                            'value' => [],
                            'field' => 'messages',
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/webhook/meta', $payload);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_post_invalid_structure_returns_ok(): void
    {
        $response = $this->postJson('/api/webhook/meta', ['invalid' => 'payload']);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ok']);
    }

    public function test_webhook_signature_verification(): void
    {
        config(['services.whatsapp.webhook_secret' => 'my-secret-key']);

        $payload = ['entry' => [['changes' => [['value' => []]]]]];
        $signature = 'sha256=' . hash_hmac('sha256', json_encode($payload), 'my-secret-key');

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => $signature,
            'Content-Type' => 'application/json',
        ])->postJson('/api/webhook/meta', $payload);

        $response->assertStatus(200);
    }

    public function test_webhook_invalid_signature_rejected(): void
    {
        config(['services.whatsapp.webhook_secret' => 'my-secret-key']);

        $response = $this->withHeaders([
            'X-Hub-Signature-256' => 'sha256=wrong-signature',
            'Content-Type' => 'application/json',
        ])->postJson('/api/webhook/meta', ['entry' => []]);

        $response->assertStatus(401);
    }

    public function test_webhook_missing_signature_rejected(): void
    {
        config(['services.whatsapp.webhook_secret' => 'my-secret-key']);

        $response = $this->postJson('/api/webhook/meta', ['entry' => []]);

        $response->assertStatus(401);
    }
}
