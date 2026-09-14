<?php

namespace App\Jobs;

use App\Services\WhatsApp\WhatsAppBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SyncWhatsAppTemplatesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 120;

    public function handle(WhatsAppBroadcastService $broadcastService): void
    {
        $count = $broadcastService->syncTemplates();

        Log::channel('whatsapp')->info('🔄 SyncWhatsAppTemplatesJob done', ['saved' => $count]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('whatsapp')->error('❌ SyncWhatsAppTemplatesJob failed', [
            'error' => $exception->getMessage(),
        ]);
    }
}