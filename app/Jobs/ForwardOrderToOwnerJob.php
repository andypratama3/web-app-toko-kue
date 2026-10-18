<?php

namespace App\Jobs;

use App\Models\Order;
use App\Services\WhatsApp\AdminNotificationRouterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Teruskan format pesanan ke WA owner cabang dengan retry + backoff.
 *
 * Dulu forward owner dipanggil sinkron di dalam alur order. Bila Meta
 * sedang gangguan sesaat, notif pesanan baru bisa hilang diam-diam —
 * owner tak tahu ada order masuk. Job ini mengulang beberapa kali
 * sebelum menyerah, dan kegagalan akhir tercatat jelas di log.
 */
class ForwardOrderToOwnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 30;

    /** Backoff eksponensial (detik) antar percobaan. */
    public array $backoff = [10, 30, 90];

    public function __construct(public int $orderId)
    {
    }

    public function handle(AdminNotificationRouterService $router): void
    {
        $order = Order::with(['customer', 'region', 'items'])->find($this->orderId);

        if (! $order) {
            Log::channel('whatsapp')->warning('⚠️ ForwardOrderToOwnerJob: order tak ditemukan', [
                'order_id' => $this->orderId,
            ]);

            return;
        }

        // Tanpa nomor owner, tak ada gunanya retry — cukup dilewati.
        if (! $order->region?->owner_phone) {
            Log::channel('whatsapp')->info('⏭️ Skip forward owner via job (owner_phone kosong)', [
                'order_id' => $order->id,
            ]);

            return;
        }

        // Gagal kirim (Meta gangguan) → lempar agar queue mengulang
        // sesuai $tries + backoff. Bila tetap gagal, method failed() mencatat.
        if (! $router->forwardOrderToOwner($order)) {
            throw new \RuntimeException("Forward pesanan {$order->invoice_number} ke owner gagal terkirim");
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('whatsapp')->error('❌ ForwardOrderToOwnerJob gagal total setelah retry', [
            'order_id' => $this->orderId,
            'error' => $exception->getMessage(),
        ]);

        // Pada driver "sync", exception job diteruskan ke pemanggil dan
        // notifikasi kegagalan dibuat oleh notifyNewOrder — hindari dobel.
        if (config('queue.default') === 'sync') {
            return;
        }

        try {
            $order = Order::find($this->orderId);

            if ($order) {
                app(AdminNotificationRouterService::class)
                    ->notifyOwnerForwardFailure($order, 'queue retry habis');
            }
        } catch (\Throwable $e) {
            Log::channel('whatsapp')->warning('⚠️ Gagal mencatat notif owner-forward failure', [
                'order_id' => $this->orderId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
