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

    public function test_haversine_distance(): void
    {
        // Denpasar to Kuta (approximately 8km)
        $distance = $this->service->estimateDistance(-8.7235, 115.1763, 3);

        $this->assertIsFloat($distance);
        $this->assertGreaterThan(0, $distance);
        $this->assertTrue(($distance >= 5 && $distance <= 12), "Expected ~8km, got {$distance}");
    }
}
