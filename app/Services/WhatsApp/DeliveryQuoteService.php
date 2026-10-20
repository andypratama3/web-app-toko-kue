<?php

namespace App\Services\WhatsApp;

use Illuminate\Support\Facades\Log;

class DeliveryQuoteService
{
    public function __construct(
        protected DeliveryZoneService $deliveryZoneService
    ) {}

    public function quote(array $ctx): array
    {
        $regionId = $ctx['region_id'] ?? null;
        $deliveryMethod = $ctx['delivery_method'] ?? null; // self_pickup|grab_gosend|internal_courier|null

        if (in_array($deliveryMethod, ['self_pickup', 'grab_gosend'], true)) {
            $result = [
                'distance_km' => null,
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => null,
                'basis' => $ctx['location_source'] ?? ($ctx['pending_location'] || $ctx['delivery_lat'] ? 'pin' : ($ctx['delivery_address'] ? 'address' : 'none')),
                'out_of_range' => false,
                'needs_pin' => false,
                'needs_escalation' => false,
                'reason' => 'non_internal_zero_ongkir',
                'location_source' => $ctx['location_source'] ?? null,
            ];
            Log::info('delivery.quote', array_filter([
                'region_id' => $regionId,
                'method' => $deliveryMethod,
                'source' => $result['basis'],
                'distance' => null,
                'ongkir' => 0,
                'reason' => $result['reason'],
            ]));

            return $result;
        }

        $pinLat = $ctx['pin_lat'] ?? $ctx['delivery_lat'] ?? null;
        $pinLng = $ctx['pin_lng'] ?? $ctx['delivery_lng'] ?? null;
        $pending = $ctx['pending_location'] ?? null;
        if (is_array($pending) && isset($pending['latitude'], $pending['longitude'])) {
            $pinLat = $pinLat ?? ($pending['latitude'] ?? null);
            $pinLng = $pinLng ?? ($pending['longitude'] ?? null);
        }

        $addressText = $ctx['address_text'] ?? $ctx['delivery_address'] ?? ($ctx['order_customer']['address'] ?? null);
        $addressDistance = $ctx['address_distance_km'] ?? null;

        if ($addressDistance === null && is_string($addressText) && trim($addressText) !== '') {
            // Only estimate from address if it doesn't look like just coordinates
            if (! preg_match('/Lokasi GPS/i', $addressText) && ! preg_match('/-?\d+\.\d+.*-?\d+\.\d+/', $addressText)) {
                $addressDistance = $this->deliveryZoneService->estimateDistanceByAddress($addressText, $regionId);
            }
        }

        // Also handle explicit pin distance
        $pinDistance = null;
        if (is_numeric($pinLat) && is_numeric($pinLng)) {
            $pinDistance = $this->deliveryZoneService->estimateDistance((float) $pinLat, (float) $pinLng, $regionId);
        } elseif ($ctx['distance_km_source'] === 'pin' && isset($ctx['distance_km'])) {
            $pinDistance = $ctx['distance_km'];
        }

        // Determine location_source - last input wins
        $locationSource = $ctx['location_source'] ?? null;
        if (! $locationSource) {
            if ($pinDistance !== null && $addressDistance === null) {
                $locationSource = 'pin';
            } elseif ($addressDistance !== null && $pinDistance === null) {
                $locationSource = 'address';
            } elseif ($pinDistance !== null && $addressDistance !== null) {
                // last input wins - check which was last updated
                $locationSource = $ctx['last_location_source'] ?? 'pin';
            } elseif ($pinDistance !== null) {
                $locationSource = 'pin';
            } elseif ($addressDistance !== null) {
                $locationSource = 'address';
            } else {
                $locationSource = null;
            }
        }

        $distanceKm = null;
        if ($locationSource === 'pin') {
            $distanceKm = $pinDistance ?? $addressDistance;
        } elseif ($locationSource === 'address') {
            $distanceKm = $addressDistance ?? $pinDistance;
        } else {
            $distanceKm = $pinDistance ?? $addressDistance;
            if ($distanceKm !== null && $pinDistance !== null && $addressDistance !== null && $pinDistance !== $addressDistance) {
                $locationSource = $ctx['last_location_source'] ?? 'pin';
                $distanceKm = ($locationSource === 'pin') ? $pinDistance : $addressDistance;
            }
        }

        // Conflict detection
        if ($pinDistance !== null && $addressDistance !== null && abs($pinDistance - $addressDistance) > config('delivery.conflict_threshold_km', 3)) {
            // Mark as needs confirmation if not already resolved
            if (! ($ctx['_conflict_resolved'] ?? false)) {
                return [
                    'distance_km' => null,
                    'ongkir' => 0,
                    'zone_id' => null,
                    'zone_name' => null,
                    'basis' => $locationSource,
                    'out_of_range' => false,
                    'needs_pin' => false,
                    'needs_escalation' => false,
                    'needs_conflict_resolution' => true,
                    'conflict' => [
                        'pin_distance' => $pinDistance,
                        'address_distance' => $addressDistance,
                    ],
                    'reason' => 'conflict_pin_address',
                    'location_source' => $locationSource,
                ];
            }
        }

        if ($distanceKm === null) {
            $result = [
                'distance_km' => null,
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => null,
                'basis' => 'none',
                'out_of_range' => false,
                'needs_pin' => true,
                'needs_escalation' => false,
                'reason' => 'needs_pin_or_address_resolution',
                'location_source' => $locationSource,
            ];
            Log::info('delivery.quote', array_filter([
                'region_id' => $regionId,
                'method' => $deliveryMethod,
                'source' => $locationSource,
                'distance' => null,
                'ongkir' => 0,
                'reason' => $result['reason'],
            ]));

            return $result;
        }

        // Check if region has no reference point properly configured
        try {
            $ref = $this->deliveryZoneService->estimateDistance((float) $pinLat, (float) $pinLng, $regionId);
        } catch (\Throwable $e) {
            // Fall through
        }

        $calc = $this->deliveryZoneService->calculateOngkir($distanceKm, $regionId);
        if (! empty($calc['needs_escalation'])) {
            $outOfRange = true;
            if (config('delivery.enforce_radius_over_zone', false) && $distanceKm <= config('delivery.max_radius_km', 14)) {
                // Only enforce if over hard radius
            }
            $result = [
                'distance_km' => $distanceKm,
                'ongkir' => 0,
                'zone_id' => null,
                'zone_name' => null,
                'basis' => $locationSource,
                'out_of_range' => $outOfRange,
                'needs_pin' => false,
                'needs_escalation' => true,
                'reason' => 'out_of_range',
                'location_source' => $locationSource,
            ];
            Log::info('delivery.quote', array_filter([
                'region_id' => $regionId,
                'method' => $deliveryMethod,
                'source' => $locationSource,
                'distance' => $distanceKm,
                'ongkir' => 0,
                'reason' => $result['reason'],
            ]));

            return $result;
        }

        $result = [
            'distance_km' => round($distanceKm, 2),
            'ongkir' => (int) ($calc['ongkir'] ?? 0),
            'zone_id' => null,
            'zone_name' => $calc['zone_name'] ?? null,
            'basis' => $locationSource,
            'out_of_range' => false,
            'needs_pin' => false,
            'needs_escalation' => false,
            'reason' => 'ok',
            'location_source' => $locationSource,
        ];
        Log::info('delivery.quote', array_filter([
            'region_id' => $regionId,
            'method' => $deliveryMethod,
            'source' => $locationSource,
            'distance' => $result['distance_km'],
            'ongkir' => $result['ongkir'],
            'reason' => $result['reason'],
        ]));

        return $result;
    }
}
