<?php

namespace App\Services\WhatsApp;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Region;
use App\Models\WhatsAppBroadcast;
use App\Models\WhatsAppBroadcastRecipient;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppTemplate;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class WhatsAppBroadcastService
{
    protected WhatsappMetaService $metaService;

    public function __construct(WhatsappMetaService $metaService)
    {
        $this->metaService = $metaService;
    }

    // ========== TEMPLATE SYNC ==========

    /**
     * Sinkronkan template dari Meta ke tabel lokal. Return jumlah template yang berhasil disimpan.
     */
    public function syncTemplates(): int
    {
        $fetched = $this->metaService->fetchTemplates('APPROVED');

        if (empty($fetched)) {
            return 0;
        }

        $saved = 0;
        foreach ($fetched as $item) {
            $name = $item['name'] ?? null;
            if (! $name) {
                continue;
            }

            $components = $item['components'] ?? [];
            $bodyText = $this->componentText($components, 'BODY');
            $headerText = $this->componentText($components, 'HEADER');
            $parametersCount = $bodyText ? $this->countParams($bodyText) : 0;

            WhatsAppTemplate::updateOrCreate(
                ['name' => $name],
                [
                    'meta_template_id' => $item['id'] ?? null,
                    'language' => $item['language'] ?? 'id',
                    'status' => $item['status'] ?? 'APPROVED',
                    'category' => $item['category'] ?? null,
                    'body_text' => $bodyText,
                    'header_text' => $headerText,
                    'button_text' => $this->componentText($components, 'BUTTONS') ?: $this->buttonLabels($components),
                    'components' => $components,
                    'parameters_count' => $parametersCount,
                    'is_active' => $this->isBroadcastable($components, $bodyText),
                    'last_synced_at' => now(),
                ]
            );

            $saved++;
        }

        Log::channel('whatsapp')->info('🔄 WhatsApp templates synced', ['saved' => $saved]);

        return $saved;
    }

    /** Cari teks komponen dengan type tertentu (BODY, HEADER). */
    protected function componentText(array $components, string $type): ?string
    {
        foreach ($components as $component) {
            if (strtoupper($component['type'] ?? '') === $type) {
                return $component['text'] ?? null;
            }
        }

        return null;
    }

    /** Gabungkan label tombol (mis. "PESAN SEKARANG") sebagai informasi UI. */
    protected function buttonLabels(array $components): ?string
    {
        $labels = [];
        foreach ($components as $component) {
            if (strtoupper($component['type'] ?? '') !== 'BUTTONS') {
                continue;
            }
            foreach ($component['buttons'] ?? [] as $button) {
                $labels[] = $button['text'] ?? null;
            }
        }

        $labels = array_values(array_filter($labels));

        return $labels ? implode(', ', $labels) : null;
    }

    /**
     * Template bisa dipakai broadcast bila:
     * - Punya teks BODY (wajib untuk pesan marketing)
     * - Header bila ada harus TEXT, IMAGE, VIDEO, atau DOCUMENT (media didukung)
     * - Tombol bila ada harus statis (tanpa parameter dinamis seperti url_suffix)
     */
    protected function isBroadcastable(array $components, ?string $bodyText): bool
    {
        if (! $bodyText) {
            return false;
        }

        foreach ($components as $component) {
            $type = strtoupper($component['type'] ?? '');

            if ($type === 'HEADER') {
                $format = strtoupper($component['format'] ?? 'TEXT');
                if (! in_array($format, ['TEXT', 'IMAGE', 'VIDEO', 'DOCUMENT', ''], true)) {
                    return false;
                }
            }

            if ($type === 'BUTTONS') {
                foreach ($component['buttons'] ?? [] as $button) {
                    if (! empty($button['url_suffix'])) {
                        return false;
                    }
                    if (! empty($button['parameters'])) {
                        return false;
                    }
                    if (($button['type'] ?? '') === 'PHONE_NUMBER' || ($button['type'] ?? '') === 'OTP') {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /** Hitung jumlah placeholder {{1}}..{{n}} dalam teks. */
    public static function countParams(string $text): int
    {
        preg_match_all('/\{\{(\d+)\}\}/', $text, $matches);

        return $matches[1] ? max($matches[1]) : 0;
    }

    /** Tipe header template (TEXT | IMAGE | VIDEO | DOCUMENT) atau null bila tidak ada header. */
    public static function headerFormat(?array $components): ?string
    {
        foreach ($components ?? [] as $component) {
            if (strtoupper($component['type'] ?? '') === 'HEADER') {
                $format = strtoupper($component['format'] ?? 'TEXT');
                return in_array($format, ['IMAGE', 'VIDEO', 'DOCUMENT', 'TEXT'], true) ? $format : 'TEXT';
            }
        }

        return null;
    }

    /** URL media contoh header (yang disetujui Meta saat review) atau null. */
    public static function headerExampleUrl(?array $components): ?string
    {
        foreach ($components ?? [] as $component) {
            if (strtoupper($component['type'] ?? '') !== 'HEADER') {
                continue;
            }

            $handle = $component['example']['header_handle'][0] ?? null;

            return is_string($handle) && str_starts_with($handle, 'http') ? $handle : null;
        }

        return null;
    }

    // ========== RECIPIENTS ==========

    /**
     * Buatkan query penerima broadcast berdasarkan filter.
     *
     * @param  array{region_id?: int|null, customer_category_id?: int|null, only_opt_in?: bool}  $filter
     */
    public function recipientQuery(array $filter): Builder
    {
        $regionId = $filter['region_id'] ?? null;
        $categoryId = $filter['customer_category_id'] ?? null;
        $onlyOptIn = (bool) ($filter['only_opt_in'] ?? true);

        return Customer::query()
            ->when($regionId, fn (Builder $q, $r) => $q->where('region_id', $r))
            ->when($categoryId, fn (Builder $q, $c) => $q->where('customer_category_id', $c))
            ->when($onlyOptIn, fn (Builder $q) => $q->whereIn('phone', $this->optInPhoneVariants()))
            ->whereNotNull('phone')
            ->where('phone', '!=', '');
    }

    /**
     * Kumpulan varian nomor dari percakapan WhatsApp (sudah dinormalisasi 62...).
     * Customer ada yang tersimpan sebagai 08xxx, 628xxx, atau 8xxx, jadi dicocokkan beberapa format.
     *
     * @return array<int, string>
     */
    protected function optInPhoneVariants(): array
    {
        $phones = WhatsAppConversation::query()->pluck('phone_number')->unique()->values();

        $variants = $phones->map(function ($phone) {
            $normalized = Phone::normalize((string) $phone);

            return [
                $normalized,
                '0'.substr($normalized, 2),
                substr($normalized, 2),
            ];
        })->flatten()->unique()->values()->all();

        return $variants;
    }

    /** Hitung jumlah penerima untuk preview sebelum broadcast dibuat. */
    public function countRecipients(array $filter): int
    {
        return $this->recipientQuery($filter)->count();
    }

    /**
     * Simpan penerima broadcast ke tabel recipient berdasar filter.
     */
    public function storeRecipients(WhatsAppBroadcast $broadcast, array $filter): int
    {
        $count = 0;

        $this->recipientQuery($filter)
            ->with('region')
            ->chunkById(500, function ($customers) use ($broadcast, &$count) {
                $rows = $customers->map(function (Customer $customer) use ($broadcast, &$count) {
                    $count++;

                    return [
                        'broadcast_id' => $broadcast->id,
                        'customer_id' => $customer->id,
                        'region_id' => $customer->region_id,
                        'phone' => Phone::normalize((string) $customer->phone),
                        'name' => $customer->name,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                })->all();

                WhatsAppBroadcastRecipient::insert($rows);
            });

        $broadcast->update([
            'recipient_count' => $count,
            'recipient_filter' => $filter,
        ]);

        return $count;
    }

    // ========== SENDING ==========

    /**
     * Bangun komponen parameter template WhatsApp untuk satu penerima.
     * Nilai parameter bisa mengandung token {nama} yang diganti nama penerima.
     *
     * @return array<int, array<string, mixed>>
     */
    public function buildComponents(WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient): array
    {
        $template = $broadcast->template;
        $parameters = $broadcast->parameters ?? [];
        $components = [];

        // Header media (IMAGE/VIDEO/DOCUMENT): tambahkan komponen header
        // bila admin menyediakan media (link publik). Tanpa media, Meta memakai
        // media bawaan template yang sudah di-approve.
        if ($broadcast->header_media_type && $broadcast->header_media_url) {
            $mediaType = strtolower($broadcast->header_media_type);
            $components[] = [
                'type' => 'header',
                'parameters' => [
                    [
                        'type' => $mediaType,
                        $mediaType => [
                            'link' => $broadcast->header_media_url,
                        ],
                    ],
                ],
            ];
        }

        foreach ($template->components ?? [] as $component) {
            $type = strtolower($component['type'] ?? '');

            if (! in_array($type, ['header', 'body'], true)) {
                continue;
            }

            // Header media sudah dipecayakan lewat komponen khusus di atas.
            if ($type === 'header' && $broadcast->header_media_type) {
                continue;
            }

            $text = $component['text'] ?? '';
            $count = self::countParams($text);

            if ($count === 0) {
                continue; // tanpa variabel tidak perlu dikirim sebagai komponen
            }

            $params = [];
            for ($i = 1; $i <= $count; $i++) {
                $raw = (string) ($parameters[$i] ?? '');
                $value = str_replace(
                    ['{nama}', '{name}', '{nama_customer}', '{recipient}'],
                    $recipient->name ?? '',
                    $raw
                );
                $params[] = [
                    'type' => 'text',
                    'text' => $value,
                ];
            }

            $components[] = ['type' => $type, 'parameters' => $params];
        }

        return $components;
    }

    /**
     * Render teks preview (untuk ditampilkan admin) setelah parameter diisi.
     */
    public function renderBodyPreview(WhatsAppTemplate $template, array $parameters, ?string $headerMediaType = null, ?string $headerMediaUrl = null): string
    {
        $text = $template->body_text ?? '';
        $header = $template->header_text ?? '';
        $preview = '';

        if ($headerMediaType && $headerMediaUrl) {
            $label = [
                'image' => 'Gambar',
                'video' => 'Video',
                'document' => 'Dokumen',
            ][strtolower($headerMediaType)] ?? ucfirst(strtolower($headerMediaType));
            $preview .= "[{$label}: {$headerMediaUrl}]\n\n";
        } elseif ($header) {
            $preview .= $header."\n\n";
        }

        $preview .= $text;
        $preview .= $template->button_text ? "\n\n[Tombol: {$template->button_text}]" : '';

        foreach ($parameters as $key => $value) {
            $preview = str_replace('{{' . $key . '}}', (string) $value, $preview);
        }

        return $preview;
    }

    /**
     * Kick-off eksekusi broadcast: ubah status jadi queued lalu dispatch job batch pertama.
     */
    public function dispatchBroadcast(WhatsAppBroadcast $broadcast): void
    {
        $broadcast->update([
            'status' => 'queued',
            'scheduled_at' => now(),
        ]);

        \App\Jobs\ProcessWhatsAppBroadcastJob::dispatch($broadcast->id, 1);

        Log::channel('whatsapp')->info('📣 Broadcast queued', [
            'broadcast_id' => $broadcast->id,
            'recipients' => $broadcast->recipient_count,
        ]);
    }

    /**
     * Proses satu batch penerima. Dipanggil dari job. Return true bila masih ada batch berikutnya.
     */
    public function processNextBatch(WhatsAppBroadcast $broadcast, int $page): bool
    {
        if ($broadcast->status === 'cancelled') {
            return false;
        }

        if ($broadcast->status !== 'processing') {
            $broadcast->update(['status' => 'processing', 'started_at' => now()]);
        }

        $batchSize = (int) config('services.whatsapp.broadcast_batch_size', 50);
        $interval = (float) config('services.whatsapp.broadcast_interval_seconds', 1);

        $pending = $broadcast->recipients()
            ->where('status', 'pending')
            ->orderBy('id')
            ->limit($batchSize)
            ->get();

        foreach ($pending as $recipient) {
            $this->sendToRecipient($broadcast, $recipient);

            if ($interval > 0) {
                usleep((int) ($interval * 1_000_000));
            }
        }

        $remaining = $broadcast->recipients()->where('status', 'pending')->count();

        // Update agregat
        $broadcast->refresh();
        $broadcast->update([
            'sent_count' => $broadcast->recipients()->where('status', 'sent')->count(),
            'failed_count' => $broadcast->recipients()->where('status', 'failed')->count(),
        ]);

        if ($remaining > 0) {
            return true;
        }

        $broadcast->update([
            'status' => $broadcast->failed_count > 0 && $broadcast->sent_count === 0 ? 'failed' : 'completed',
            'finished_at' => now(),
        ]);

        Log::channel('whatsapp')->info('📣 Broadcast finished', [
            'broadcast_id' => $broadcast->id,
            'sent' => $broadcast->sent_count,
            'failed' => $broadcast->failed_count,
        ]);

        return false;
    }

    protected function sendToRecipient(WhatsAppBroadcast $broadcast, WhatsAppBroadcastRecipient $recipient): void
    {
        try {
            $phoneNumberId = $recipient->region?->meta_phone_number_id ?? null;
            $components = $this->buildComponents($broadcast, $recipient);

            $messageId = $this->metaService->sendTemplate(
                $recipient->phone,
                $broadcast->template->name,
                $broadcast->template->language ?: 'id',
                $components,
                $phoneNumberId
            );

            if ($messageId) {
                $recipient->update([
                    'status' => 'sent',
                    'message_id' => $messageId,
                    'sent_at' => now(),
                ]);
            } else {
                $recipient->update([
                    'status' => 'failed',
                    'error' => 'Gagal mengirim via WhatsApp API (response tidak berhasil).',
                ]);
            }
        } catch (\Exception $e) {
            Log::channel('whatsapp')->error('❌ Broadcast send failed', [
                'broadcast_id' => $broadcast->id,
                'recipient_id' => $recipient->id,
                'error' => $e->getMessage(),
            ]);

            $recipient->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);
        }
    }
}