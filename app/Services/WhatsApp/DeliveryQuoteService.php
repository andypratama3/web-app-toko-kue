<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

/**
 * Hitung ongkir + jarak untuk satu pesanan.
 *
 * Kontrak hasil (selalu punya key yang sama, supaya consumer tak pernah
 * salah baca):
 *   distance_km              float|null  jarak hasil hitung (null = tak diketahui)
 *   ongkir                   int         ongkir akhir (0 hanya jika memang gratis /
 *                                         jarak tak diketahui — consumer WAJIB
 *                                         mengecek needs_pin sebelum memakai)
 *   zone_id / zone_name      int|null|string|null  zona admin yang dipakai
 *   basis                    string      'pin' | 'address' | 'none'
 *   out_of_range             bool        jarak di luar jangkauan kurir internal
 *   needs_pin                bool        jarak tak terhitung → minta pin/location
 *   needs_escalation         bool        perlu eskalasi ke admin
 *   needs_conflict_resolution bool      pin &alamat beda jauh → ongkir diambil
 *                                         dari sumber TERAKHIR (last input wins)
 *   conflict                 array|null  detail pin vs alamat
 *   reason                   string      alasan (untuk log)
 *   location_source          string|null sumber lokasi yang dipakai
 */
class DeliveryQuoteService
{
    public function __construct(
        protected DeliveryZoneService $deliveryZoneService
    ) {}

    public function quote(array $ctx): array
    {
        $regionId = $ctx['region_id'] ?? null;
        $deliveryMethod = $ctx['delivery_method'] ?? null;

        // Ambil sendiri & Grab/GoSend: ongkir 0, tak perlu zona kurir internal.
        if (in_array($deliveryMethod, ['self_pickup', 'grab_gosend'], true)) {
            return $this->result([
                'distance_km' => null,
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => null,
                'basis' => $this->resolveBasis($ctx),
                'out_of_range' => false,
                'needs_pin' => false,
                'needs_escalation' => false,
                'needs_conflict_resolution' => false,
                'conflict' => null,
                'reason' => 'non_internal_zero_ongkir',
                'location_source' => $ctx['location_source'] ?? null,
            ], $regionId, $deliveryMethod);
        }

        $pinLat = $ctx['pin_lat'] ?? $ctx['delivery_lat'] ?? null;
        $pinLng = $ctx['pin_lng'] ?? $ctx['delivery_lng'] ?? null;

        $pending = $ctx['pending_location'] ?? null;
        if (is_array($pending) && isset($pending['latitude'], $pending['longitude'])) {
            $pinLat = $pinLat ?? $pending['latitude'];
            $pinLng = $pinLng ?? $pending['longitude'];
        }

        $addressText = $ctx['address_text'] ?? $ctx['delivery_address'] ?? ($ctx['order_customer']['address'] ?? null);
        $addressDistance = $ctx['address_distance_km'] ?? null;

        if ($addressDistance === null && is_string($addressText) && trim($addressText) !== '') {
            // Hanya estimasi dari teks alamat — jangan dari koordinat yang
            // tersalin ke dalam kolom alamat.
            if (! preg_match('/Lokasi GPS/i', $addressText) && ! preg_match('/-?\d+\.\d+.*-?\d+\.\d+/', $addressText)) {
                $addressDistance = $this->deliveryZoneService->estimateDistanceByAddress($addressText, $regionId);
            }
        }

        $pinDistance = null;
        if (is_numeric($pinLat) && is_numeric($pinLng)) {
            $pinDistance = $this->deliveryZoneService->estimateDistance((float) $pinLat, (float) $pinLng, $regionId);
        } elseif (($ctx['distance_km_source'] ?? null) === 'pin' && isset($ctx['distance_km'])) {
            $pinDistance = (float) $ctx['distance_km'];
        }

        // Sumber terakhir yang dipakai user — dasar aturan "last input wins".
        $lastSource = $ctx['last_location_source'] ?? $ctx['location_source'] ?? null;
        $locationSource = $this->resolveLocationSource($ctx, $pinDistance, $addressDistance, $lastSource);

        // Konflik: jarak dari pin & dari alamat beda jauh. Tetap hitung ongkir
        // dari sumber TERAKHIR — jangan pernah mengembalikan ongkir 0 di sini,
        // karena consumer bisa membacanya sebagai "gratis".
        $conflict = null;
        $needsConflict = false;
        $threshold = (float) config('delivery.conflict_threshold_km', 3);
        if ($pinDistance !== null && $addressDistance !== null
            && abs($pinDistance - $addressDistance) > $threshold) {
            $needsConflict = true;
            $conflict = [
                'pin_distance' => $pinDistance,
                'address_distance' => $addressDistance,
            ];
            $locationSource = $this->resolveLocationSource($ctx, $pinDistance, $addressDistance, $lastSource, true);
        }

        $distanceKm = $this->pickDistance($locationSource, $pinDistance, $addressDistance);

        // Jarak tak terhitung → consumer wajib minta pin/location lebih jelas.
        if ($distanceKm === null) {
            return $this->result([
                'distance_km' => null,
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => null,
                'basis' => 'none',
                'out_of_range' => false,
                'needs_pin' => true,
                'needs_escalation' => false,
                'needs_conflict_resolution' => $needsConflict,
                'conflict' => $conflict,
                'reason' => 'needs_pin_or_address_resolution',
                'location_source' => $locationSource,
            ], $regionId, $deliveryMethod);
        }

        $calc = $this->deliveryZoneService->calculateOngkir($distanceKm, $regionId);

        if (! empty($calc['needs_escalation'])) {
            return $this->result([
                'distance_km' => round($distanceKm, 2),
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => $calc['zone_name'] ?? null,
                'basis' => $locationSource,
                'out_of_range' => true,
                'needs_pin' => false,
                'needs_escalation' => true,
                'needs_conflict_resolution' => $needsConflict,
                'conflict' => $conflict,
                'reason' => 'out_of_range',
                'location_source' => $locationSource,
            ], $regionId, $deliveryMethod);
        }

        return $this->result([
            'distance_km' => round($distanceKm, 2),
            'ongkir' => (int) ($calc['ongkir'] ?? 0),
            'zone_id' => $calc['zone_id'] ?? null,
            'zone_name' => $calc['zone_name'] ?? null,
            'basis' => $locationSource,
            'out_of_range' => false,
            'needs_pin' => false,
            'needs_escalation' => false,
            'needs_conflict_resolution' => $needsConflict,
            'conflict' => $conflict,
            'reason' => $needsConflict ? 'conflict_pin_address' : 'ok',
            'location_source' => $locationSource,
        ], $regionId, $deliveryMethod);
    }

    /**
     * Sumber lokasi yang dipakai: input terakhir user menang ("last input wins").
     */
    protected function resolveLocationSource(
        array $ctx,
        ?float $pinDistance,
        ?float $addressDistance,
        ?string $lastSource,
        bool $onConflict = false
    ): ?string {
        $hasPin = $pinDistance !== null;
        $hasAddress = $addressDistance !== null;

        // Sumber hanya sah bila jaraknya memang terhitung.
        $explicit = $ctx['location_source'] ?? null;
        if ($explicit === 'pin' && ! $hasPin) {
            $explicit = null;
        }
        if ($explicit === 'address' && ! $hasAddress) {
            $explicit = null;
        }

        if ($lastSource === 'pin' && ! $hasPin) {
            $lastSource = 'address';
        }
        if ($lastSource === 'address' && ! $hasAddress) {
            $lastSource = 'pin';
        }

        if ($onConflict) {
            // Saat konflik, andalkan sumber terakhir; fallback ke yang ada.
            if ($lastSource === 'pin' || $lastSource === 'address') {
                return $lastSource;
            }

            if ($explicit === 'pin' || $explicit === 'address') {
                return $explicit;
            }

            return $hasPin ? 'pin' : 'address';
        }

        if ($explicit === 'pin' || $explicit === 'address') {
            return $explicit;
        }

        if ($lastSource === 'pin' || $lastSource === 'address') {
            return $lastSource;
        }

        return $hasPin ? 'pin' : 'address';
    }

    protected function pickDistance(?string $source, ?float $pinDistance, ?float $addressDistance): ?float
    {
        if ($source === 'pin') {
            return $pinDistance ?? $addressDistance;
        }

        if ($source === 'address') {
            return $addressDistance ?? $pinDistance;
        }

        return $pinDistance ?? $addressDistance;
    }

    /**
     * Basis untuk metode tanpa ongkir (self pickup / Grab): dari mana lokasi
     * datang, biar log tetap informatif.
     */
    protected function resolveBasis(array $ctx): string
    {
        $source = $ctx['location_source'] ?? null;
        if ($source === 'pin' || $source === 'address') {
            return $source;
        }

        $pending = $ctx['pending_location'] ?? null;
        if (is_array($pending) && ! empty($pending)) {
            return 'pin';
        }

        if (! empty($ctx['delivery_lat']) || ! empty($ctx['pin_lat'])) {
            return 'pin';
        }

        if (! empty($ctx['delivery_address']) || ! empty($ctx['address_text'])) {
            return 'address';
        }

        return 'none';
    }

    protected function result(array $result, ?int $regionId, ?string $method): array
    {
        Log::info('delivery.quote', array_filter([
            'region_id' => $regionId,
            'method' => $method,
            'source' => $result['basis'] ?? null,
            'distance' => $result['distance_km'] ?? null,
            'ongkir' => $result['ongkir'] ?? null,
            'reason' => $result['reason'] ?? null,
        ], fn ($value) => $value !== null));

        return $result;
    }
}
