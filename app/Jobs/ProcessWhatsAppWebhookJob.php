<?php

namespace App\Jobs;

use App\Enums\OrderBotConversationState;
use App\Events\WhatsAppMessageReceived;
use App\Models\Region;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppMessageStatus;
use App\Models\WhatsAppBroadcastRecipient;
use App\Support\Phone;
use App\Services\WhatsApp\IncomingMediaHandler;
use App\Services\WhatsApp\OrderBotService;
use App\Services\WhatsApp\ConversationRegionResolver;
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
    public int $timeout = 120;

    protected array $payload;
    protected ?string $phoneNumberId;
    protected ?ConversationRegionResolver $regionResolver = null;

    public function __construct(array $payload, ?string $phoneNumberId = null)
    {
        $this->payload = $payload;
        $this->phoneNumberId = $phoneNumberId;
    }

    public function handle(
        WhatsappMetaService $metaService,
        OrderBotService $botService,
        IncomingMediaHandler $mediaHandler,
        ConversationRegionResolver $regionResolver
    ): void {
        $this->regionResolver = $regionResolver;

        // Meta dapat menggabungkan beberapa entry/changes dalam 1 delivery —
        // proses SEMUANYA (bukan hanya [0][0]) agar tak ada pesan yang hilang.
        foreach ($this->payload['entry'] ?? [] as $entry) {
            foreach ($entry['changes'] ?? [] as $change) {
                $this->processChange($change, $metaService, $botService, $mediaHandler);
            }
        }
    }

    protected function processChange(
        array $change,
        WhatsappMetaService $metaService,
        OrderBotService $botService,
        IncomingMediaHandler $mediaHandler
    ): void {
        $value = $change['value'] ?? [];

        // Profil dulu agar percakapan baru langsung punya nama.
        if (isset($value['contacts'])) {
            foreach ($value['contacts'] as $contact) {
                $this->updateConversationProfile($contact);
            }
        }

        // Nomor pengirim per-change (batch multi-nomor punya metadata sendiri).
        $changePhoneNumberId = $value['metadata']['phone_number_id'] ?? $this->phoneNumberId;

        // Status dan pesan diproses independen (bisa sekantong dalam 1 value).
        if (isset($value['statuses'])) {
            $this->processStatuses($value['statuses']);
        }

        if (isset($value['messages'])) {
            foreach ($value['messages'] as $message) {
                $this->processMessage($message, $value, $metaService, $botService, $mediaHandler, $changePhoneNumberId);
            }
        }
    }

    protected function processMessage(
        array $message,
        array $value,
        WhatsappMetaService $metaService,
        OrderBotService $botService,
        IncomingMediaHandler $mediaHandler,
        ?string $phoneNumberId = null
    ): void {
        $phone = $message['from'] ?? null;
        $messageId = $message['id'] ?? null;

        if (!$phone || !$messageId) {
            Log::channel('whatsapp')->warning('⚠️ Missing phone or message ID', ['message' => $message]);
            return;
        }

        $phone = Phone::normalize($phone);

        // Dedup cepat sebelum kerja mahal (unduh media). Klaim atomik menyusul
        // tepat sebelum insert untuk menutup celah balapan antar-worker.
        if (WhatsAppMessage::where('whatsapp_message_id', $messageId)->exists()) {
            Log::channel('whatsapp')->info('⏭️ Duplicate message skipped', ['message_id' => $messageId]);
            return;
        }

        // Get or create conversation
        $conversation = $this->getOrCreateConversation($phone, $value, $phoneNumberId);

        // Mark as read
        $metaService->markAsRead($messageId, $phoneNumberId ?? $this->phoneNumberId);

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

        // Klaim atomik: balapan dua worker diselesaikan unique index —
        // yang kalah skip SEBELUM efek samping apa pun (bot belum jalan).
        try {
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
        } catch (\Illuminate\Database\QueryException $e) {
            if (WhatsAppMessage::where('whatsapp_message_id', $messageId)->exists()) {
                Log::channel('whatsapp')->info('⏭️ Duplicate message skipped (race)', ['message_id' => $messageId]);

                return;
            }

            throw $e;
        }

        // Tipe tak didukung (stiker/video/audio/dokumen/dll): catat + balas sopan,
        // JANGAN masuk state machine agar alur order tak tercemar "[video]" dkk.
        if (! in_array($messageType, ['text', 'image', 'location', 'interactive'], true)) {
            $conversation->incrementMessageCount();
            $awaitingProof = $conversation->current_state === OrderBotConversationState::AWAITING_PAYMENT_PROOF->value
                && in_array($messageType, ['video', 'document', 'audio'], true);
            $metaService->sendText($conversation->phone_number, $awaitingProof
                ? 'Untuk bukti bayar, kirim *foto/screenshot* (JPG/PNG), bukan video/dokumen ya 🙏'
                : 'Maaf, format pesan ini belum didukung. Kirim teks, foto/gambar, atau lokasi (pin GPS) ya 🙏'
            );

            return;
        }

        // Gambar gagal diunduh: minta kirim ulang, jangan maju ke bot sebagai bukti.
        if ($messageType === 'image' && isset($messageData['media_id']) && empty($messageData['media_path'])) {
            $conversation->incrementMessageCount();
            $metaService->sendText($conversation->phone_number,
                'Mohon maaf, foto tidak terunduh. Silakan kirim ulang sebagai *gambar/foto* (bukan dokumen).'
            );

            return;
        }

        // Reset percakapan yang terbengkalai sebelum memproses (supaya user selalu bisa mulai baru)
        $botService->resetConversationIfStale($conversation, $conversation->last_message_at);

        $conversation->incrementMessageCount();

        WhatsAppMessageReceived::dispatch($savedMessage, $conversation->phone_number, $content ?? '');

        Log::channel('whatsapp')->info('📩 Incoming message', [
            'phone' => $phone,
            'type' => $messageType,
            'content' => substr($content ?? '', 0, 100),
            'state' => $conversation->current_state,
        ]);

        // Atribusi cabang DUA tahap: sebelum bot (sumber customer/alamat lama agar
        // katalog & ongkir pesan ini memakai cabang yang benar) + sesudah bot
        // (bot baru saja bisa menulis alamat baru di context).
        $this->regionResolver->apply($conversation);
        $conversation->refresh();

        // Route message to bot
        if ($messageType === 'location') {
            $botService->handleLocationMessage($conversation, $messageData);
        } else {
            $botService->handleMessage($conversation, $content ?? '', $messageData);
        }

        $this->regionResolver->apply($conversation->refresh());
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

            // Broadcast: jikalau Meta memakai status failed (mis. media header ditolak / nomor tak valid),
            // pantulkan kegagalan itu ke recipient broadcast agar statistik tidak menyesatkan.
            if ($statusValue === 'failed') {
                $recipient = WhatsAppBroadcastRecipient::where('message_id', $messageId)->first();

                if ($recipient) {
                    $errorText = is_array($errors) ? json_encode($errors) : (string) $errors;
                    $recipient->update(['status' => 'failed', 'error' => $errorText]);

                    $broadcast = $recipient->broadcast;
                    if ($broadcast && ! in_array($broadcast->status, ['completed', 'failed', 'cancelled'], true)) {
                        $broadcast->refresh();
                        $broadcast->update([
                            'sent_count' => $broadcast->recipients()->where('status', 'sent')->count(),
                            'failed_count' => $broadcast->recipients()->where('status', 'failed')->count(),
                        ]);
                    }
                }
            }

            Log::channel('whatsapp')->info('📨 Message status update', [
                'message_id' => $messageId,
                'status' => $statusValue,
            ]);
        }
    }

    protected function getOrCreateConversation(string $phone, array $value, ?string $phoneNumberId = null): WhatsAppConversation
    {
        // Extract region from phone number or default
        $regionId = $this->detectRegionFromContext($value, $phoneNumberId);

        try {
            $conversation = WhatsAppConversation::firstOrCreate(
                ['phone_number' => $phone],
                [
                    'profile_name' => $value['contacts'][0]['profile']['name'] ?? null,
                    'region_id' => $regionId,
                    'status' => 'active',
                    'current_state' => OrderBotConversationState::INIT->value,
                ]
            );

            if ($conversation->wasRecentlyCreated) {
                Log::channel('whatsapp')->info('🆕 New conversation created', [
                    'phone' => $phone,
                    'region_id' => $regionId,
                ]);
            }

            return $conversation;
        } catch (\Illuminate\Database\QueryException $e) {
            // Balapan dua pesan pertama: ambil baris pemenang via unique index.
            $existing = WhatsAppConversation::where('phone_number', $phone)->first();
            if ($existing) {
                return $existing;
            }

            throw $e;
        }
    }

    protected function detectRegionFromContext(array $value, ?string $phoneNumberId = null): ?int
    {
        // Prefer the region bound to the incoming phone number (multi-cabang)
        $numberId = $phoneNumberId ?? $this->phoneNumberId;
        $regionFromNumber = $numberId ? Region::findByPhoneNumberId($numberId) : null;
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

        // Jangan gagal diam-diam: munculkan di dashboard admin agar ditindaklanjuti.
        try {
            \App\Models\AdminNotification::create([
                'user_id' => null,
                'region_id' => null,
                'type' => 'system',
                'title' => 'Webhook WhatsApp gagal diproses',
                'message' => 'Pesan masuk gagal diproses 3x: ' . mb_substr($exception->getMessage(), 0, 500)
                    . '. Cek log whatsapp & hubungi customer bila perlu.',
                'is_read' => false,
            ]);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('⚠️ Gagal mencatat notif job-failed', ['error' => $e->getMessage()]);
        }
    }
}
