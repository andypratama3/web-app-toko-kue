<?php

namespace Tests\Support;

use App\Services\WhatsApp\WhatsappMetaService;

/**
 * Rekaman pesan keluar untuk characterization test OrderBotService.
 *
 * Tidak mengirim apa pun ke Graph API: setiap panggilan disimpan di $sent
 * sehingga test bisa memeriksa teks/struktur yang benar-benar dikirim bot.
 */
class RecordingWhatsappMetaService extends WhatsappMetaService
{
    /** @var array<int, array<string, mixed>> */
    public array $sent = [];

    public function reset(): void
    {
        $this->sent = [];
    }

    public function sendText(string $to, string $text, bool $record = true, ?string $phoneNumberId = null): ?string
    {
        return $this->capture('text', $to, ['text' => $text]);
    }

    public function sendImage(string $to, string $imageUrl, ?string $caption = null, ?string $phoneNumberId = null): ?string
    {
        return $this->capture('image', $to, ['image_url' => $imageUrl, 'caption' => $caption]);
    }

    public function sendListMessage(string $to, string $bodyText, array $sections, ?string $footerText = null, ?string $phoneNumberId = null): ?string
    {
        $rows = [];
        foreach ($sections as $section) {
            foreach ($section['rows'] ?? [] as $row) {
                $rows[] = $row['title'] ?? '';
            }
        }

        return $this->capture('list', $to, [
            'text' => $bodyText,
            'sections' => $sections,
            'rows' => $rows,
            'footer' => $footerText,
        ]);
    }

    public function sendReplyButtons(string $to, string $bodyText, array $buttons, ?string $footerText = null, ?string $phoneNumberId = null): ?string
    {
        return $this->capture('buttons', $to, [
            'text' => $bodyText,
            'buttons' => array_map(
                fn ($button) => $button['reply']['title'] ?? $button['title'] ?? '',
                $buttons
            ),
            'footer' => $footerText,
        ]);
    }

    public function sendLocationRequest(string $to, string $bodyText, ?string $phoneNumberId = null): ?string
    {
        return $this->capture('location_request', $to, ['text' => $bodyText]);
    }

    public function sendTemplate(string $to, string $templateName, string $languageCode = 'id', array $components = [], ?string $phoneNumberId = null): ?string
    {
        return $this->capture('template', $to, [
            'template' => $templateName,
            'language' => $languageCode,
            'components' => $components,
        ]);
    }

    public function markAsRead(string $messageId, ?string $phoneNumberId = null): void
    {
        $this->capture('mark_as_read', null, ['message_id' => $messageId]);
    }

    public function sendTypingOn(string $messageId, ?string $phoneNumberId = null): void
    {
        $this->capture('typing_on', null, ['message_id' => $messageId]);
    }

    public function downloadMedia(string $mediaId): ?string
    {
        $this->capture('download_media', null, ['media_id' => $mediaId]);

        return null;
    }

    public function getPhoneNumberId(): string
    {
        return 'test-phone-number-id';
    }

    public function fetchTemplates(?string $status = 'APPROVED', int $limit = 100): array
    {
        return [];
    }

    /**
     * Seluruh teks yang dikirim bot (teks biasa, body list, body tombol).
     */
    public function texts(): array
    {
        $texts = [];
        foreach ($this->sent as $message) {
            $text = $message['payload']['text'] ?? null;
            if (is_string($text) && $text !== '') {
                $texts[] = $text;
            }
        }

        return $texts;
    }

    public function allText(): string
    {
        return implode("\n---\n", $this->texts());
    }

    public function hasText(string $needle): bool
    {
        return str_contains($this->allText(), $needle);
    }

    public function lastText(): ?string
    {
        $texts = $this->texts();

        return $texts === [] ? null : end($texts);
    }

    public function messagesOfType(string $type): array
    {
        return array_values(array_filter($this->sent, fn ($message) => $message['type'] === $type));
    }

    protected function capture(string $type, ?string $to, array $payload): string
    {
        $this->sent[] = ['type' => $type, 'to' => $to, 'payload' => $payload];

        return 'wamid_recorded_'.count($this->sent);
    }
}
