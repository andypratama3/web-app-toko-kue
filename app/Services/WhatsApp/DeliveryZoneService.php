<?php

namespace App\Services\WhatsApp;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneService
{
    public function calculateOngkir(float $distanceKm, ?int $regionId): array
    {
        $cacheKey = 'ongkir_' . ($regionId ?? 'null') . '_' . $distanceKm;
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        // Zona eksplisit admin MENANG dulu (mis. Gresik/Ubud 15km Rp15.000) —
        // eskalasi hanya bila tak ada zona yang mencakup + jarak > 14km.
        $zone = DeliveryZone::active()
            ->forRegion($regionId)
            ->where('distance_km', '>=', $distanceKm)
            ->orderBy('distance_km')
            ->first();

        if ($zone) {
            $result = [
                'ongkir' => (int) $zone->ongkir,
                'needs_escalation' => false,
                'distance_km' => $distanceKm,
                'zone_name' => $zone->area_name,
            ];
            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;
        }

        if ($distanceKm > 14) {
            return [
                'ongkir' => 0,
                'needs_escalation' => true,
                'message' => 'Jarak di atas 14km, eskalasi ke admin.',
            ];
        }

        $result = [
            'ongkir' => $this->calculateTierOngkir($distanceKm),
            'needs_escalation' => false,
            'distance_km' => $distanceKm,
            'zone_name' => null,
        ];

        Cache::put($cacheKey, $result, now()->addHours(24));

        return $result;
    }

    protected function calculateTierOngkir(float $distanceKm): int
    {
        return match(true) {
            $distanceKm < 10 => 10000,
            $distanceKm <= 14 => 15000,
            default => 0,
        };
    }

    public function estimateDistance(float $lat, float $lng, ?int $regionId): float
    {
        $referencePoints = $this->getReferencePoint($regionId);
        $distance = $this->haversineDistance(
            $lat, $lng,
            $referencePoints['lat'], $referencePoints['lng']
        );

        return round($distance, 2);
    }

    /**
     * Estimasi jarak dari teks alamat memakai tabel delivery_zones milik cabang:
     * cocokkan keyword landmark / nama area (yang cocok paling panjang menang),
     * pakai distance_km zona tersebut. Tanpa kecocokan → default 5.0km.
     */
    public function estimateDistanceByAddress(string $address, ?int $regionId): float
    {
        // Normalisasi tanda baca jadi spasi agar "Gubeng," tetap cocok "gubeng".
        $normalized = trim((string) preg_replace('/[^a-z0-9\s]+/i', ' ', strtolower($address)));
        $normalized = (string) preg_replace('/\s+/', ' ', $normalized);
        $haystack = ' ' . $normalized . ' ';

        $zones = DeliveryZone::active()->forRegion($regionId)->get();

        $bestDistance = null;
        $bestKeywordLength = 0;

        foreach ($zones as $zone) {
            $area = strtolower(trim((string) $zone->area_name));
            $area = trim((string) preg_replace('/[^a-z0-9\s]+/i', ' ', $area));
            $area = trim((string) preg_replace('/\s+/', ' ', $area));
            $keywords = array_merge($this->splitZoneKeywords($zone->landmark_keyword), [$area]);

            foreach ($keywords as $keyword) {
                if (strlen($keyword) < 4) {
                    continue;
                }
                // Batas kata (haystack diapit spasi) agar "batu" tak cocok "batubara".
                if (str_contains($haystack, ' ' . $keyword . ' ') && strlen($keyword) > $bestKeywordLength) {
                    $bestKeywordLength = strlen($keyword);
                    $bestDistance = (float) $zone->distance_km;
                }
            }
        }

        return $bestDistance ?? 5.0;
    }

    protected function splitZoneKeywords(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        $parts = preg_split('/[,\/;\n]+/', $raw) ?: [];

        return array_values(array_filter(array_map(
            function ($p) {
                $p = strtolower(trim((string) $p));
                $p = trim((string) preg_replace('/[^a-z0-9\s]+/i', ' ', $p));
                return trim((string) preg_replace('/\s+/', ' ', $p));
            },
            $parts
        )));
    }

    protected function getReferencePoint(?int $regionId): array
    {
        // Resolve via slug agar tak bergantung urutan auto-increment id.
        $knownLocations = [
            'surabaya' => ['lat' => -7.2575, 'lng' => 112.7521],
            'malang' => ['lat' => -7.9666, 'lng' => 112.6326],
            'denpasar' => ['lat' => -8.6500, 'lng' => 115.2167],
        ];

        $region = $regionId ? \App\Models\Region::find($regionId) : null;
        $slug = strtolower((string) ($region->slug ?? ''));

        if (isset($knownLocations[$slug])) {
            return $knownLocations[$slug];
        }

        \Illuminate\Support\Facades\Log::channel('whatsapp')->warning('⚠️ Titik referensi tak dikenal, pakai Denpasar', [
            'region_id' => $regionId,
            'slug' => $slug,
        ]);

        return $knownLocations['denpasar'];
    }

    protected function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng / 2) * sin($dLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    public function getActiveZones(?int $regionId = null)
    {
        $query = DeliveryZone::active();

        if ($regionId) {
            $query->forRegion($regionId);
        }

        return $query->orderBy('distance_km')->get();
    }
}
