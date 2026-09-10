<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappMetaService
{
    protected string $phoneNumberId;
    protected string $token;
    protected string $version;

    public function __construct()
    {
        $this->phoneNumberId = config('services.whatsapp.phone_number_id');
        $this->token = config('services.whatsapp.access_token');
        $this->version = config('services.whatsapp.graph_version');
    }

    protected function client()
    {
        return Http::withToken($this->token)->timeout(30);
    }

    protected function url(string $phoneNumberId): string
    {
        return "https://graph.facebook.com/{$this->version}/{$phoneNumberId}";
    }

    protected function resolveOutboundNumber(string $recipientPhone, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        $conversation = WhatsAppConversation::where('phone_number', $recipientPhone)->first();
        if ($conversation && $conversation->region?->meta_phone_number_id) {
            return $conversation->region->meta_phone_number_id;
        }

        return $this->phoneNumberId;
    }

    protected function resolveNumberForMessage(string $messageId, ?string $override = null): string
    {
        if ($override) {
            return $override;
        }

        $message = WhatsAppMessage::where('whatsapp_message_id', $messageId)->first();
        if ($message && $message->conversation?->region?->meta_phone_number_id) {
            return $message->conversation->region->meta_phone_number_id;
        }

        return $this->phoneNumberId;
    }

    public function sendText(string $to, string $text, bool $record = true, ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => $text],
        ];

        return $this->sendMessage($payload, $to, 'text', $record, $phoneNumberId);
    }

    public function sendImage(string $to, string $imageUrl, ?string $caption = null, ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'image',
            'image' => array_filter([
                'link' => $imageUrl,
                'caption' => $caption,
            ]),
        ];

        return $this->sendMessage($payload, $to, 'image', true, $phoneNumberId);
    }

    public function sendListMessage(string $to, string $bodyText, array $sections, ?string $footerText = null, ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => $bodyText],
                'action' => [
                    'button' => 'Lihat Pilihan',
                    'sections' => $sections,
                ],
            ],
        ];

        if ($footerText) {
            $payload['interactive']['footer'] = ['text' => $footerText];
        }

        return $this->sendMessage($payload, $to, 'interactive', true, $phoneNumberId);
    }

    public function sendReplyButtons(string $to, string $bodyText, array $buttons, ?string $footerText = null, ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $bodyText],
                'action' => [
                    'buttons' => array_map(fn($btn) => [
                        'type' => 'reply',
                        'reply' => [
                            'id' => $btn['id'],
                            'title' => $btn['title'],
                        ],
                    ], $buttons),
                ],
            ],
        ];

        if ($footerText) {
            $payload['interactive']['footer'] = ['text' => $footerText];
        }

        return $this->sendMessage($payload, $to, 'interactive', true, $phoneNumberId);
    }

    public function sendLocationRequest(string $to, string $bodyText, ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'location_request_message',
                'body' => ['text' => $bodyText],
                'action' => ['name' => 'send_location'],
            ],
        ];

        return $this->sendMessage($payload, $to, 'interactive', true, $phoneNumberId);
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode = 'id', array $components = [], ?string $phoneNumberId = null): ?string
    {
        $payload = [
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => ['code' => $languageCode],
            ],
        ];

        if (!empty($components)) {
            $payload['template']['components'] = $components;
        }

        return $this->sendMessage($payload, $to, 'template', true, $phoneNumberId);
    }

    public function markAsRead(string $messageId, ?string $phoneNumberId = null): void
    {
        try {
            $this->client()->post("{$this->url($this->resolveNumberForMessage($messageId, $phoneNumberId))}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'read',
                'message_id' => $messageId,
            ]);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Failed to mark message as read', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function sendTypingOn(string $messageId, ?string $phoneNumberId = null): void
    {
        try {
            $this->client()->post("{$this->url($this->resolveNumberForMessage($messageId, $phoneNumberId))}/messages", [
                'messaging_product' => 'whatsapp',
                'status' => 'typing_on',
                'message_id' => $messageId,
            ]);
        } catch (\Exception $e) {
            Log::channel('whatsapp')->warning('⚠️ Failed to send typing indicator', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function downloadMedia(string $mediaId): ?string
    {
        try {
            $mediaResponse = $this->client()->get("https://graph.facebook.com/{$this->version}/{$mediaId}");

            if (!$mediaResponse->successful()) {
                Log::channel('whatsapp')->error('❌ Failed to get media URL', [
                    'media_id' => $mediaId,
                    'status' => $mediaResponse->status(),
                ]);
                return null;
            }

            $mediaUrl = $mediaResponse->json('url');
            $imageResponse = $this->client()->get($mediaUrl);

            if (!$imageResponse->successful()) {
                Log::channel('whatsapp')->error('❌ Failed to download media', [
                    'media_id' => $mediaId,
                    'url' => $mediaUrl,
                ]);
                return null;
            }

            $contentType = $imageResponse->header('Content-Type', 'image/jpeg');
            $extension = match(true) {
                str_contains($contentType, 'image/jpeg') => 'jpg',
                str_contains($contentType, 'image/png') => 'png',
                str_contains($contentType, 'image/webp') => 'webp',
                str_contains($contentType, 'video/mp4') => 'mp4',
                default => 'bin',
            };

            $filename = 'wa_media_' . time() . '_' . uniqid() . '.' . $extension;
            $path = "payment_proofs/{$filename}";

            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $imageResponse->body());

            return $path;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Failed to download media', [
                'media_id' => $mediaId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    public function getPhoneNumberId(): string
    {
        return $this->phoneNumberId;
    }

    protected function sendMessage(array $payload, string $recipientPhone, string $type, bool $record = true, ?string $phoneNumberId = null): ?string
    {
        try {
            $outboundId = $this->resolveOutboundNumber($recipientPhone, $phoneNumberId);
            $response = $this->client()->post("{$this->url($outboundId)}/messages", $payload);

            if ($response->successful()) {
                $messageId = $response->json('messages.0.id');
                Log::channel('whatsapp')->info("📤 Message sent [{$type}]", [
                    'to' => $recipientPhone,
                    'message_id' => $messageId,
                    'type' => $type,
                ]);

                if ($record) {
                    WhatsAppMessage::create([
                        'conversation_id' => $this->getConversationId($recipientPhone),
                        'whatsapp_message_id' => $messageId ?? 'pending_' . uniqid(),
                        'sender_type' => 'bot',
                        'message_type' => $type,
                        'content' => $this->extractContentForLog($payload, $type),
                        'status' => 'sent',
                    ]);
                }

                return $messageId;
            }

            Log::channel('whatsapp')->error('❌ Failed to send message', [
                'to' => $recipientPhone,
                'status' => $response->status(),
                'body' => $response->json(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Exception sending message', [
                'to' => $recipientPhone,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    protected function extractContentForLog(array $payload, string $type): ?string
    {
        $content = $payload[$type]['body']['text']
            ?? $payload[$type]['body']
            ?? $payload[$type]['text']
            ?? null;

        if (is_array($content)) {
            return json_encode($content, JSON_UNESCAPED_UNICODE) ?: null;
        }

        return is_scalar($content) ? (string) $content : null;
    }

    protected function getConversationId(string $phoneNumber): ?string
    {
        $conversation = WhatsAppConversation::where('phone_number', $phoneNumber)->first();
        return $conversation?->id;
    }
}
