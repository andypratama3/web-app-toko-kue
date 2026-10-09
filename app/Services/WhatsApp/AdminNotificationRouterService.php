<?php

namespace App\Services\WhatsApp;

use App\Models\AdminNotification;
use App\Models\Order;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

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

        // Forward format pesanan ke WA owner cabang (tidak menggagalkan alur bila gagal)
        try {
            $this->forwardOrderToOwner($order);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('⚠️ Forward ke owner gagal (order tetap tersimpan)', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Kirim format pesanan + instruksi konfirmasi ke nomor WA owner cabang.
     * Nomor diambil dari `regions.owner_phone` (format 62…).
     * Bila kosong/belum diisi, hanya dicatat di log agar alur bot tidak terganggu.
     */
    public function forwardOrderToOwner(Order $order): void
    {
        $order->loadMissing(['customer', 'region', 'items']);

        $ownerPhone = $order->region?->owner_phone
            ? Phone::normalize($order->region->owner_phone)
            : null;

        if (! $ownerPhone) {
            Log::channel('whatsapp')->info('⏭️ Skip forward ke owner (owner_phone cabang belum diisi)', [
                'order_id' => $order->id,
                'region_id' => $order->region_id,
            ]);

            return;
        }

        $this->metaService->sendText($ownerPhone, $this->buildOwnerOrderFormat($order));

        Log::channel('whatsapp')->info('📲 Format pesanan diteruskan ke owner', [
            'order_id' => $order->id,
            'invoice' => $order->invoice_number,
            'owner' => $ownerPhone,
        ]);
    }

    /**
     * Teruskan bukti bayar customer ke WA owner cabang (gambar + caption invoice).
     */
    public function forwardPaymentProof(Order $order, ?string $mediaPath = null): void
    {
        try {
            $this->doForwardPaymentProof($order, $mediaPath);
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('⚠️ Forward bukti bayar ke owner gagal', [
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    protected function doForwardPaymentProof(Order $order, ?string $mediaPath = null): void
    {
        $order->loadMissing(['customer', 'region', 'items']);

        $ownerPhone = $order->region?->owner_phone
            ? Phone::normalize($order->region->owner_phone)
            : null;

        if (! $ownerPhone) {
            Log::channel('whatsapp')->info('⏭️ Skip forward bukti bayar (owner_phone cabang belum diisi)', [
                'order_id' => $order->id,
                'region_id' => $order->region_id,
            ]);

            return;
        }

        $path = $mediaPath ?: $order->payment_proof;
        $caption = "🧾 *Bukti Bayar Masuk*\n"
            . "Invoice: *{$order->invoice_number}*\n"
            . 'Total: *Rp ' . number_format($order->total_amount, 0, ',', '.') . "*\n"
            . "Customer: {$order->customer->name}\n\n"
            . 'Mohon konfirmasi pembayaran. Balas via Chat Monitor / hubungi customer bila perlu.';

        $imageUrl = $path ? $this->publicUrl($path) : null;

        if ($imageUrl) {
            $sent = $this->metaService->sendImage($ownerPhone, $imageUrl, $caption);
            if ($sent) {
                Log::channel('whatsapp')->info('📲 Bukti bayar diteruskan ke owner', [
                    'order_id' => $order->id,
                    'invoice' => $order->invoice_number,
                    'owner' => $ownerPhone,
                ]);

                return;
            }
        }

        // Fallback: tanpa gambar (file lokal / URL tak reachable) — owner tetap dapat notif teks
        $this->metaService->sendText($ownerPhone, $caption . "\n\n(Catatan: gambar bukti tidak terlampir otomatis — cek di halaman verifikasi admin.)");
    }

    protected function buildOwnerOrderFormat(Order $order): string
    {
        $items = $order->items->map(function ($item) {
            $variant = $item->variant_name ? " ({$item->variant_name})" : '';

            return "• {$item->product_name}{$variant} x{$item->quantity} — Rp " . number_format($item->subtotal, 0, ',', '.');
        })->implode("\n");

        $customer = $order->customer;

        return "🧾 *PESANAN BARU via WhatsApp*\n"
            . "Cabang: {$order->region->name}\n"
            . "Invoice: *{$order->invoice_number}*\n"
            . "─────────────────\n"
            . "{$items}\n"
            . "─────────────────\n"
            . '*TOTAL: Rp ' . number_format($order->total_amount, 0, ',', '.') . "*\n\n"
            . "Penerima: {$customer->name}\n"
            . "HP customer: {$order->phone}\n"
            . "Alamat: {$order->address}\n"
            . "Bayar via: {$order->payment_method}\n"
            . ($order->note ? "Catatan:\n{$order->note}\n" : '')
            . "\nBukti bayar akan diteruskan menyusul di pesan berikutnya.\n"
            . 'Konfirmasi di: Admin → Verifikasi Pesanan.';
    }

    protected function publicUrl(string $path): ?string
    {
        // Meta butuh URL publik absolut. Storage::url() mengembalikan path relatif (/storage/...).
        if (str_starts_with($path, 'http')) {
            return $path;
        }

        $relative = str_starts_with($path, '/') ? $path : Storage::url($path);

        return rtrim(config('app.url'), '/') . $relative;
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

        // Notify customer if they have a WhatsApp conversation (yang terbaru)
        $conversation = \App\Models\WhatsAppConversation::where('customer_id', $order->customer_id)
            ->where('status', 'active')
            ->orderByDesc('last_message_at')
            ->first();

        if ($conversation) {
            $statusMessages = [
                'dikemas' => "📦 Pesanan Anda sedang dikemas.",
                'diambil' => "🚚 Pesanan Anda sedang diambil kurir.",
                'diantar' => "🚛 Pesanan Anda sedang dalam perjalanan!",
                'diterima_pembeli' => "✅ Pesanan Anda telah diterima. Terima kasih!",
                'selesai' => "🎉 Pesanan selesai! Semoga puas dengan produk kami.",
                'menunggu_verifikasi_admin' => "🔎 Pesanan Anda sedang diverifikasi admin.",
                'dibatalkan' => "❌ Pesanan Anda dibatalkan. Hubungi admin untuk info lebih lanjut.",
            ];

            $message = $statusMessages[$newStatus] ?? null;
            if ($message) {
                $this->metaService->sendText($conversation->phone_number, $message);
            } else {
                Log::channel('whatsapp')->info('ℹ️ Status tanpa pesan customer', [
                    'order_id' => $order->id,
                    'new_status' => $newStatus,
                ]);
            }
        }
    }
}