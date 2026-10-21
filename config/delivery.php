<?php

return [
    'max_radius_km' => env('DELIVERY_MAX_RADIUS_KM', 14),
    'enforce_radius_over_zone' => env('DELIVERY_ENFORCE_RADIUS_OVER_ZONE', false),
    'conflict_threshold_km' => env('DELIVERY_CONFLICT_THRESHOLD_KM', 3),
    'strict_no_denpasar_fallback' => env('DELIVERY_STRICT_NO_DENPASAR_FALLBACK', false),
    'fee_tiers' => [
        '<'.env('DELIVERY_FEE_TIER_1_KM', 10) => (int) env('DELIVERY_FEE_TIER_1', 10000),
        '<='.env('DELIVERY_FEE_TIER_2_KM', 14) => (int) env('DELIVERY_FEE_TIER_2', 15000),
    ],
    'fee_tier_1_max_km' => (float) env('DELIVERY_FEE_TIER_1_KM', 10),
    'fee_tier_2_max_km' => (float) env('DELIVERY_FEE_TIER_2_KM', 14),
    'fee_tier_1_amount' => (int) env('DELIVERY_FEE_TIER_1', 10000),
    'fee_tier_2_amount' => (int) env('DELIVERY_FEE_TIER_2', 15000),
];
