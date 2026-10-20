<?php

return [
    'max_radius_km' => env('DELIVERY_MAX_RADIUS_KM', 14),
    'enforce_radius_over_zone' => env('DELIVERY_ENFORCE_RADIUS_OVER_ZONE', false),
    'conflict_threshold_km' => env('DELIVERY_CONFLICT_THRESHOLD_KM', 3),
    'fee_tiers' => [
        '<10' => 10000,
        '<=14' => 15000,
    ],
];
