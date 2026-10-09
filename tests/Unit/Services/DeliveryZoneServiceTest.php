<?php

namespace Tests\Unit\Services;

use App\Services\WhatsApp\DeliveryZoneService;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeliveryZoneServiceTest extends TestCase
{
    use RefreshDatabase;

    protected DeliveryZoneService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(DeliveryZoneService::class);
    }

    public function test_ongkir_under_10km(): void
    {
        $result = $this->service->calculateOngkir(5.0, 3);

        $this->assertEquals(10000, $result['ongkir']);
        $this->assertFalse($result['needs_escalation']);
    }

    public function test_ongkir_10_to_14km(): void
    {
        $result = $this->service->calculateOngkir(12.0, 3);

        $this->assertEquals(15000, $result['ongkir']);
        $this->assertFalse($result['needs_escalation']);
    }

    public function test_ongkir_over_14km_escalates(): void
    {
        $result = $this->service->calculateOngkir(15.0, 3);

        $this->assertTrue($result['needs_escalation']);
    }

    public function test_ongkir_exactly_10km(): void
    {
        $result = $this->service->calculateOngkir(10.0, 3);

        $this->assertEquals(15000, $result['ongkir']);
        $this->assertFalse($result['needs_escalation']);
    }

    public function test_ongkir_exactly_14km(): void
    {
        $result = $this->service->calculateOngkir(14.0, 3);

        $this->assertEquals(15000, $result['ongkir']);
        $this->assertFalse($result['needs_escalation']);
    }

    public function test_ongkir_exactly_9km(): void
    {
        $result = $this->service->calculateOngkir(9.99, 3);

        $this->assertEquals(10000, $result['ongkir']);
    }

    public function test_explicit_zone_wins_over_escalation(): void
    {
        $region = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        \App\Models\DeliveryZone::create([
            'region_id' => $region->id,
            'area_name' => 'Gresik',
            'landmark_keyword' => 'gresik, manyar',
            'distance_km' => 15.0,
            'ongkir' => 15000,
            'is_active' => true,
        ]);

        $result = $this->service->calculateOngkir(14.5, $region->id);

        $this->assertFalse($result['needs_escalation']);
        $this->assertEquals(15000, $result['ongkir']);
        $this->assertEquals('Gresik', $result['zone_name']);
    }

    public function test_null_region_falls_back_to_tier_without_crash(): void
    {
        $result = $this->service->calculateOngkir(5.0, null);

        $this->assertFalse($result['needs_escalation']);
        $this->assertEquals(10000, $result['ongkir']);
    }

    public function test_address_estimator_uses_zone_keywords(): void
    {
        $region = Region::create(['name' => 'Surabaya', 'slug' => 'surabaya']);
        \App\Models\DeliveryZone::create([
            'region_id' => $region->id,
            'area_name' => 'Sidoarjo',
            'landmark_keyword' => 'sidoarjo, gedangan',
            'distance_km' => 12.0,
            'ongkir' => 15000,
            'is_active' => true,
        ]);

        $this->assertEquals(12.0, $this->service->estimateDistanceByAddress('Jl. Gedangan No 5, Sidoarjo', $region->id));
        // Tak cocok zona mana pun → default 5.0
        $this->assertEquals(5.0, $this->service->estimateDistanceByAddress('Jl. Mawar No 1', $region->id));
        // "Batubara" tidak boleh cocok keyword "batu"
        $malang = Region::create(['name' => 'Malang', 'slug' => 'malang']);
        \App\Models\DeliveryZone::create([
            'region_id' => $malang->id,
            'area_name' => 'Batu',
            'landmark_keyword' => 'batu, jatim park',
            'distance_km' => 10.0,
            'ongkir' => 15000,
            'is_active' => true,
        ]);
        $this->assertEquals(5.0, $this->service->estimateDistanceByAddress('Jl. Batubara No 2', $malang->id));
    }

    public function test_haversine_distance(): void
    {
        // Denpasar to Kuta (approximately 8km)
        $distance = $this->service->estimateDistance(-8.7235, 115.1763, 3);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        $this->assertTrue(($distance >= 5 && $distance <= 12), "Expected ~8km, got {$distance}");
    }
}
