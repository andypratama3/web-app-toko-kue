<?php

namespace App\Jobs;

use App\Enums\OrderBotConversationState;
use App\Events\WhatsAppMessageReceived;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageStatus;
use App\Support\Phone;
use App\Services\WhatsApp\IncomingMediaHandler;
use App\Services\WhatsApp\OrderBotService;
use App\Services\WhatsApp\WhatsappMetaService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 60;

    protected array $payload;
    protected ?string $phoneNumberId;

    public function __construct(array $payload, ?string $phoneNumberId = null)
    {
        $this->payload = $payload;
        $this->phoneNumberId = $phoneNumberId;
    }

    public function handle(
        WhatsappMetaService $metaService,
        OrderBotService $botService,
        IncomingMediaHandler $mediaHandler
    ): void {
        $entry = $this->payload['entry'][0] ?? null;
        if (!$entry) return;

        $changes = $entry['changes'][0] ?? null;
        if (!$changes) return;

        $value = $changes['value'] ?? [];

        // Process status updates
        if (isset($value['statuses'])) {
            $this->processStatuses($value['statuses']);
            return;
        }

        // Process incoming messages
        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->processMessage($message, $value, $metaService, $botService, $mediaHandler);
            }
        }

        // Process contacts (for profile info)
        if (isset($value['contacts'])) {
            foreach ($value['contacts'] as $contact) {
                $this->updateConversationProfile($contact);
            }
        }
    }

    protected function processMessage(
        array $message,
        array $value,
        WhatsappMetaService $metaService,
        OrderBotService $botService,
        IncomingMediaHandler $mediaHandler
    ): void {
        $phone = $message['from'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$phone || !$messageId) {
            Log::channel('whatsapp')->warning('⚠️ Missing phone or message ID', ['message' => $message]);
            return;
        }

        $phone = Phone::normalize($phone);

        // Dedup check
        if (WhatsAppMessage::where('whatsapp_message_id', $messageId)->exists()) {
            Log::channel('whatsapp')->info('⏭️ Duplicate message skipped', ['message_id' => $messageId]);
            return;
        }

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($phone, $value);

        // Mark as read
        $metaService->markAsRead($messageId, $this->phoneNumberId);

        // Determine message type and content
        $messageType = $message['type'] ?? 'text';
        $content = null;
        $messageData = null;

        switch ($messageType) {
            case 'text':
                $content = $message['text']['body'] ?? '';
                break;

            case 'image':
                $content = $message['image']['caption'] ?? '[Gambar]';
                $mediaId = $message['image']['id'] ?? null;
                if ($mediaId) {
                    $mediaPath = $mediaHandler->handlePaymentProof($mediaId);
                    $messageData = ['media_id' => $mediaId, 'media_path' => $mediaPath];
                }
                break;

            case 'location':
                $content = "Lokasi: {$message['location']['latitude']}, {$message['location']['longitude']}";
                $messageData = [
                    'latitude' => $message['location']['latitude'] ?? null,
                    'longitude' => $message['location']['longitude'] ?? null,
                    'name' => $message['location']['name'] ?? null,
                    'address' => $message['location']['address'] ?? null,
                ];
                break;

            case 'interactive':
                $content = $this->normalizeInteractiveInput($this->extractInteractiveContent($message));
                break;

            default:
                $content = "[{$messageType}]";
        }

        // Save incoming message
        $savedMessage = WhatsAppMessage::create([
            'conversation_id' => $conversation->id,
            'whatsapp_message_id' => $messageId,
            'sender_type' => 'customer',
            'message_type' => $messageType,
            'content' => $content,
            'media_url' => $messageData['media_path'] ?? null,
            'media_type' => $messageType === 'image' ? 'image' : null,
            'status' => 'received',
        ]);

        $conversation->incrementMessageCount();

        WhatsAppMessageReceived::dispatch($savedMessage, $conversation->phone_number, $content ?? '');

        Log::channel('whatsapp')->info('📩 Incoming message', [
            'phone' => $phone,
            'type' => $messageType,
            'content' => substr($content ?? '', 0, 100),
            'state' => $conversation->current_state,
        ]);

        // Route message to bot
        if ($messageType === 'location') {
            $botService->handleLocationMessage($conversation, $messageData);
        } else {
            $botService->handleMessage($conversation, $content ?? '', $messageData);
        }
    }

    protected function processStatuses(array $statuses): void
    {
        foreach ($statuses as $status) {
            $messageId = $status['id'] ?? null;
            $statusValue = $status['status'] ?? null;
            $timestamp = $status['timestamp'] ?? null;
            $recipientId = $status['recipient_id'] ?? null;
            $errors = $status['errors'] ?? null;

            if (!$messageId || !$statusValue) continue;

            WhatsAppMessageStatus::updateOrCreate(
                ['message_id' => $messageId, 'status' => $statusValue],
                [
                    'recipient' => $recipientId,
                    'timestamp' => $timestamp ? date('Y-m-d H:i:s', (int)$timestamp) : null,
                    'errors' => $errors,
                ]
            );

            // Update message status
            $message = WhatsAppMessage::where('whatsapp_message_id', $messageId)->first();
            if ($message) {
                $statusPriority = ['sent' => 0, 'delivered' => 1, 'read' => 2, 'failed' => 3];
                $currentPriority = $statusPriority[$message->status] ?? -1;
                $newPriority = $statusPriority[$statusValue] ?? -1;

                if ($newPriority > $currentPriority) {
                    $message->update(['status' => $statusValue]);
                }
            }

            Log::channel('whatsapp')->info('📨 Message status update', [
                'message_id' => $messageId,
                'status' => $statusValue,
            ]);
        }
    }

    protected function getOrCreateConversation(string $phone, array $value): WhatsAppConversation
    {
        $conversation = WhatsAppConversation::where('phone_number', $phone)->first();

        if (!$conversation) {
            // Extract region from phone number or default
            $regionId = $this->detectRegionFromContext($value);

            $conversation = WhatsAppConversation::create([
                'phone_number' => $phone,
                'profile_name' => $value['contacts'][0]['profile']['name'] ?? null,
                'region_id' => $regionId,
                'status' => 'active',
                'current_state' => OrderBotConversationState::INIT->value,
            ]);

            Log::channel('whatsapp')->info('🆕 New conversation created', [
                'phone' => $phone,
                'region_id' => $regionId,
            ]);
        }

        return $conversation;
    }

    protected function detectRegionFromContext(array $value): ?int
    {
        // Prefer the region bound to the incoming phone number (multi-cabang)
        $regionFromNumber = $this->phoneNumberId ? Region::findByPhoneNumberId($this->phoneNumberId) : null;
        if ($regionFromNumber) {
            return $regionFromNumber->id;
        }

        // Default region from config
        $defaultRegionName = config('services.whatsapp.default_region', 'Denpasar');
        $region = Region::where('name', 'like', "%{$defaultRegionName}%")->first();
        return $region?->id;
    }

    protected function extractInteractiveContent(array $message): string
    {
        $interactive = $message['interactive'] ?? [];
        $type = $interactive['type'] ?? '';

        return match ($type) {
            'button_reply' => $interactive['button_reply']['id'] ?? $interactive['button_reply']['title'] ?? '',
            'list_reply' => $interactive['list_reply']['id'] ?? $interactive['list_reply']['title'] ?? '',
            default => '[Interactive]',
        };
    }

    protected function normalizeInteractiveInput(string $content): string
    {
        if (preg_match('/^(delivery|slot)_(\d)$/', $content, $m)) {
            return $m[2];
        }

        return match ($content) {
            'cat_tumpeng' => 'tumpeng',
            'cat_hampers' => 'hampers',
            'cat_alacarte' => 'ala carte',
            default => $content,
        };
    }

    protected function updateConversationProfile(array $contact): void
    {
        $phone = Phone::normalize($contact['wa_id'] ?? '');
        $name = $contact['profile']['name'] ?? null;

        if ($phone && $name) {
            WhatsAppConversation::where('phone_number', $phone)
                ->update(['profile_name' => $name]);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('whatsapp')->error('❌ ProcessWhatsAppWebhookJob failed', [
            'error' => $exception->getMessage(),
            'payload' => $this->payload,
        ]);
    }
}
