<?php

namespace App\Services\WhatsApp;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneService
{
    public function calculateOngkir(float $distanceKm, ?int $regionId): array
    {
        if ($distanceKm > 14) {
            return [
                'ongkir' => 0,
                'needs_escalation' => true,
                'message' => 'Jarak di atas 14km, eskalasi ke admin.',
            ];
        }

        $cached = Cache::get("ongkir_{$regionId}_{$distanceKm}");
        if ($cached !== null) {
            return $cached;
        }

        $zone = DeliveryZone::active()
            ->forRegion($regionId)
            ->where('distance_km', '>=', $distanceKm)
            ->orderBy('distance_km')
            ->first();

        $ongkir = $zone?->ongkir ?? $this->calculateTierOngkir($distanceKm);

        $result = [
            'ongkir' => $ongkir,
            'needs_escalation' => false,
            'distance_km' => $distanceKm,
            'zone_name' => $zone?->area_name ?? null,
        ];

        Cache::put("ongkir_{$regionId}_{$distanceKm}", $result, now()->addHours(24));

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

    public function estimateDistanceByAddress(string $address, ?int $regionId): float
    {
        $knownLocations = [
            'denpasar' => ['lat' => -8.6500, 'lng' => 115.2167],
            'surabaya' => ['lat' => -7.2575, 'lng' => 112.7521],
            'malang' => ['lat' => -7.9666, 'lng' => 112.6326],
        ];

        $region = $regionId ? \App\Models\Region::find($regionId) : null;
        $regionSlug = strtolower($region->slug ?? 'denpasar');
        $ref = $knownLocations[$regionSlug] ?? $knownLocations['denpasar'];

        $lowerAddress = strtolower($address);

        $estimatedDistance = 5.0;

        foreach (['ubud', 'gianyar', 'kuta', 'seminyak', 'canggu', 'sanur', 'nusa dua', 'ujung'] as $area) {
            if (str_contains($lowerAddress, $area)) {
                $estimatedDistance = match($area) {
                    'kuta', 'sanur' => 8.0,
                    'seminyak', 'canggu' => 12.0,
                    'ubud', 'gianyar' => 15.0,
                    'nusa dua', 'ujung' => 16.0,
                    default => 5.0,
                };
                break;
            }
        }

        return $estimatedDistance;
    }

    protected function getReferencePoint(?int $regionId): array
    {
        $points = [
            1 => ['lat' => -7.2575, 'lng' => 112.7521], // Surabaya
            2 => ['lat' => -7.9666, 'lng' => 112.6326], // Malang
            3 => ['lat' => -8.6500, 'lng' => 115.2167], // Denpasar
        ];

        return $points[$regionId] ?? $points[3];
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
