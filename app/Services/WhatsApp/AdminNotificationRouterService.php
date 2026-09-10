<?php

namespace App\Services\WhatsApp;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class AdminNotificationRouterService
{
    protected WhatsappMetaService $metaService;

    public function __construct(WhatsappMetaService $metaService)
    {
        $this->metaService = $metaService;
    }

    public function notifyNewOrder(int $orderId): void
    {
        $order = Order::with(['customer', 'region', 'items.product'])->find($orderId);

        if (!$order) {
            return;
        }

        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where('region_id', $order->region_id)
            ->get();

        if ($admins->isEmpty()) {
            Log::channel('whatsapp')->warning('⚠️ No admin found for region', [
                'region_id' => $order->region_id,
                'order_id' => $orderId,
            ]);
            return;
        }

        $items = $order->items->map(function ($item) {
            $variant = $item->variant_name ?? '-';
            return "  • {$item->product_name} ({$variant}) x{$item->quantity}";
        })->implode("\n");

        $customerName = $order->customer->name ?? '-';

        foreach ($admins as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'region_id' => $order->region_id,
                'order_id' => $order->id,
                'type' => 'new_order',
                'title' => 'Pesanan baru via WhatsApp',
                'message' => "Invoice: {$order->invoice_number}\n"
                    . "Customer: {$customerName}\n"
                    . "Total: Rp " . number_format($order->total_amount, 0, ',', '.') . "\n"
                    . "Items:\n{$items}",
            ]);
        }

        Log::channel('whatsapp')->info('🔔 New order notification saved', [
            'order_id' => $orderId,
            'invoice' => $order->invoice_number,
            'region' => $order->region->name ?? 'Unknown',
            'admins' => $admins->count(),
        ]);
    }

    public function notifyOrderStatusUpdate(int $orderId, string $oldStatus, string $newStatus): void
    {
        $order = Order::with(['customer', 'region'])->find($orderId);

        if (!$order) {
            return;
        }

        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where('region_id', $order->region_id)
            ->get();

        foreach ($admins as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'region_id' => $order->region_id,
                'order_id' => $order->id,
                'type' => 'order_status',
                'title' => "Order {$order->invoice_number} → {$newStatus}",
                'message' => "Status pesanan berubah dari '{$oldStatus}' menjadi '{$newStatus}'.",
            ]);
        }

        Log::channel('whatsapp')->info('📋 Order status updated', [
            'order_id' => $orderId,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'channel' => $order->channel ?? 'web',
        ]);

        if (($order->channel ?? 'web') !== 'whatsapp') {
            return;
        }

        // Notify customer if they have a WhatsApp conversation
        $conversation = \App\Models\WhatsAppConversation::where('customer_id', $order->customer_id)
            ->where('status', 'active')
            ->first();

        if ($conversation) {
            $statusMessages = [
                'dikemas' => "📦 Pesanan Anda sedang dikemas.",
                'diambil' => "🚚 Pesanan Anda sedang diambil kurir.",
                'diantar' => "🚛 Pesanan Anda sedang dalam perjalanan!",
                'diterima_pembeli' => "✅ Pesanan Anda telah diterima. Terima kasih!",
                'selesai' => "🎉 Pesanan selesai! Semoga puas dengan produk kami.",
                'dibatalkan' => "❌ Pesanan Anda dibatalkan. Hubungi admin untuk info lebih lanjut.",
            ];

            $message = $statusMessages[$newStatus] ?? null;
            if ($message) {
                $this->metaService->sendText($conversation->phone_number, $message);
            }
        }
    }
}