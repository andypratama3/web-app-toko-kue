<?php

namespace App\Observers;

use App\Models\DeliveryZone;
use Illuminate\Support\Facades\Cache;

class DeliveryZoneObserver
{
    public function created(DeliveryZone $deliveryZone): void
    {
        $this->invalidate($deliveryZone);
    }

    public function updated(DeliveryZone $deliveryZone): void
    {
        $this->invalidate($deliveryZone);
    }

    public function deleted(DeliveryZone $deliveryZone): void
    {
        $this->invalidate($deliveryZone);
    }

    public function restored(DeliveryZone $deliveryZone): void
    {
        $this->invalidate($deliveryZone);
    }

    public function forceDeleted(DeliveryZone $deliveryZone): void
    {
        $this->invalidate($deliveryZone);
    }

    protected function invalidate(DeliveryZone $deliveryZone): void
    {
        $regionId = $deliveryZone->region_id;
        if ($regionId) {
            Cache::increment('delivery_zones_ver_'.$regionId);
        }
        Cache::increment('delivery_zones_ver_all');
    }
}
