<?php

namespace App\Http\Controllers\Chatbot;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessWhatsAppWebhookJob;
use App\Models\Region;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function verify(Request $request)
    {
        $verifyToken = config('services.whatsapp.verify_token');

        $mode = $request->hub_mode;
        $token = $request->hub_verify_token;
        $challenge = $request->hub_challenge;

        if ($mode === 'subscribe' && $token === $verifyToken) {
            Log::channel('whatsapp')->info('✅ Webhook verified successfully');
            return response($challenge, 200);
        }

        Log::channel('whatsapp')->warning('❌ Webhook verification failed', [
            'mode' => $mode,
            'token' => $token,
        ]);

        return response('Forbidden', 403);
    }

    public function handle(Request $request)
    {
        $payload = $request->all();

        // Verify signature — FAIL LOUDB (C1): a missing secret must never allow webhooks through
        $webhookSecret = config('services.whatsapp.webhook_secret');
        abort_if(empty($webhookSecret), 500, 'META_WEBHOOK_SECRET is not configured — webhook disabled');

        $signature = $request->header('X-Hub-Signature-256') ?? '';
        abort_unless($signature !== '', 401, 'Missing webhook signature');

        $expectedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $webhookSecret);
        abort_unless(hash_equals($expectedSignature, $signature), 401, 'Invalid webhook signature');

        // Log incoming webhook
        Log::channel('whatsapp')->info('📥 Webhook received', [
            'object' => $payload['object'] ?? null,
            'entry_count' => count($payload['entry'] ?? []),
        ]);

        // Validate structure
        if (!isset($payload['entry'][0]['changes'][0]['value'])) {
            Log::channel('whatsapp')->warning('⚠️ Invalid webhook payload structure');
            return response()->json(['status' => 'ok']);
        }

        // Resolve the phone number this event belongs to (multi-cabang routing)
        $phoneNumberId = $payload['entry'][0]['changes'][0]['value']['metadata']['phone_number_id'] ?? null;
        abort_unless($phoneNumberId, 400, 'Missing phone_number_id in webhook payload');

        $isKnownNumber = Region::findByPhoneNumberId($phoneNumberId) !== null
            || $phoneNumberId == config('services.whatsapp.phone_number_id');
        abort_unless($isKnownNumber, 403, 'Webhook from unknown phone number');

        // Dispatch job for async processing (non-blocking)
        ProcessWhatsAppWebhookJob::dispatch($payload, $phoneNumberId);

        // Respond immediately to Meta (< 5 seconds)
        return response()->json(['status' => 'ok']);
    }
}
