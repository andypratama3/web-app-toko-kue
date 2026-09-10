<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\WhatsApp\AdminNotificationRouterService;

class OrderObserver
{
    public function updated(Order $order): void
    {
        $oldStatus = $order->getOriginal('status');
        $newStatus = $order->status;

        if ($oldStatus !== $newStatus) {
            app(AdminNotificationRouterService::class)
                ->notifyOrderStatusUpdate($order->id, $oldStatus, $newStatus);
        }
    }
}