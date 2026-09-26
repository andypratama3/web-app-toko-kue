<?php

namespace App\Services\WhatsApp;

use App\Models\DeliveryZone;
use App\Models\Region;
use App\Models\WhatsAppConversation;

class ConversationRegionResolver
{
    /**
     * Sumber atribusi cabang yang tidak boleh ditimpa otomatis.
     * 'default' = hanya tebakan region bawaan, boleh dikoreksi.
     */
    public const SOURCE_CUSTOMER = 'customer';
    public const SOURCE_ADDRESS = 'address';
    public const SOURCE_MANUAL = 'manual';
    public const SOURCE_DEFAULT = 'default';

    protected const LOCKED_SOURCES = [self::SOURCE_MANUAL, self::SOURCE_CUSTOMER];

    public function __construct(protected DeliveryZoneService $deliveryZoneService) {}

    /**
     * Tentukan cabang percakapan dari customer terdaftar, lalu dari alamat.
     * Satu nomor WhatsApp dipakai semua cabang sehingga nomor tidak bisa
     * membedakan cabang dan tidak dipakai sebagai sinyal.
     *
     * @return array{0: ?int, 1: ?string} [region_id, sumber atribusi]
     */
    public function resolveWithSource(WhatsAppConversation $conversation): array
    {
        $customerRegion = $this->fromCustomer($conversation);

        if ($customerRegion !== null) {
            return [$customerRegion, self::SOURCE_CUSTOMER];
        }

        $address = $this->extractAddress($conversation);
        $addressRegion = $address ? $this->regionIdForAddress($address) : null;

        if ($addressRegion !== null) {
            return [$addressRegion, self::SOURCE_ADDRESS];
        }

        return [null, null];
    }

    public function resolve(WhatsAppConversation $conversation): ?int
    {
        return $this->resolveWithSource($conversation)[0];
    }

    /**
     * Terapkan atribusi cabang ke percakapan.
     * Atribusi manual maupun dari customer tidak pernah ditimpa.
     */
    public function apply(WhatsAppConversation $conversation, bool $force = false): ?int
    {
        [$regionId, $source] = $this->resolveWithSource($conversation);

        if ($regionId === null) {
            return $conversation->region_id;
        }

        $currentSource = $this->attributionSource($conversation);

        if (! $force && in_array($currentSource, self::LOCKED_SOURCES, true)) {
            return $conversation->region_id;
        }

        $this->persist($conversation, $regionId, $source ?? $currentSource ?? self::SOURCE_DEFAULT);

        return $regionId;
    }

    /**
     * Tetapkan cabang secara manual. Tidak akan ditimpa otomatis.
     */
    public function assignManually(WhatsAppConversation $conversation, int $regionId): void
    {
        $this->persist($conversation, $regionId, self::SOURCE_MANUAL);
    }

    /**
     * Isi cabang untuk percakapan yang masih memakai tebakan default.
     */
    public function syncUnresolved(int $limit = 200): int
    {
        $updated = 0;

        WhatsAppConversation::query()
            ->with('customer')
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->each(function (WhatsAppConversation $conversation) use (&$updated) {
                if ($conversation->region_id && ! $this->isDefault($conversation)) {
                    return;
                }

                [$regionId, $source] = $this->resolveWithSource($conversation);

                if ($regionId === null) {
                    return;
                }

                $this->persist($conversation, $regionId, $source ?? self::SOURCE_DEFAULT);
                $updated++;
            });

        return $updated;
    }

    public function attributionSource(WhatsAppConversation $conversation): ?string
    {
        $source = $conversation->getContext('branch_source');

        return is_string($source) && $source !== '' ? $source : null;
    }

    public function isDefault(WhatsAppConversation $conversation): bool
    {
        return $this->attributionSource($conversation) === null;
    }

    public function fromCustomer(WhatsAppConversation $conversation): ?int
    {
        $regionId = $conversation->customer?->region_id;

        return $regionId ? (int) $regionId : null;
    }

    /**
     * Alamat bisa berasal dari alur GPS atau form data pesanan.
     */
    public function extractAddress(WhatsAppConversation $conversation): ?string
    {
        $context = $conversation->context ?? [];

        $candidates = [
            data_get($context, 'delivery_address'),
            data_get($context, 'order_form.recipient_address'),
            data_get($context, 'address'),
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && trim($candidate) !== '') {
                return trim($candidate);
            }
        }

        return null;
    }

    /**
     * Cocokkan alamat ke keyword landmark tiap delivery zone.
     * Bobot: nama kota/region > nama area > keyword spesifik.
     */
    public function regionIdForAddress(string $address): ?int
    {
        $haystack = $this->normalize($address);

        $zones = DeliveryZone::query()->where('is_active', true)->get();
        $regions = Region::query()->get()->keyBy('id');

        $best = null;
        $bestScore = 0;

        foreach ($zones as $zone) {
            $score = $this->scoreZone($haystack, $zone, $regions->get($zone->region_id));

            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $zone->region_id;
            }
        }

        return $bestScore > 0 ? (int) $best : null;
    }

    protected function persist(
        WhatsAppConversation $conversation,
        int $regionId,
        string $source
    ): void {
        $context = $conversation->context ?? [];
        $context['branch_source'] = $source;

        $conversation->update([
            'region_id' => $regionId,
            'context' => $context,
        ]);
    }

    protected function scoreZone(string $haystack, DeliveryZone $zone, ?Region $region): int
    {
        $score = 0;

        foreach ($this->splitKeywords($zone->landmark_keyword) as $keyword) {
            $length = mb_strlen($keyword);

            if ($length >= 6 && str_contains($haystack, $keyword)) {
                $score = max($score, 20 + min($length, 20));
            }
        }

        $area = $this->normalize((string) $zone->area_name);

        if ($area !== '' && mb_strlen($area) >= 4 && str_contains($haystack, $area)) {
            $score = max($score, 30);
        }

        if ($region) {
            foreach (array_filter([$region->name, $region->slug]) as $name) {
                $name = $this->normalize((string) $name);

                if ($name !== '' && mb_strlen($name) >= 4 && str_contains($haystack, $name)) {
                    $score = max($score, 35);
                }
            }
        }

        return $score;
    }

    protected function splitKeywords(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $parts = preg_split('/[,\/;\n]+|\s{2,}/', $raw) ?: [];

        return array_values(array_filter(
            array_map(fn (string $part): string => $this->normalize($part), $parts),
            fn (string $part): bool => $part !== ''
        ));
    }

    protected function normalize(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\s]+/u', ' ', $value) ?? $value;

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }
}
