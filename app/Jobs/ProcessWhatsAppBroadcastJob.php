<?php

namespace App\Jobs;

use App\Models\WhatsAppBroadcast;
use App\Services\WhatsApp\WhatsAppBroadcastService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppBroadcastJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 3600;

    public int $broadcastId;
    public int $page;

    public function __construct(int $broadcastId, int $page = 1)
    {
        $this->broadcastId = $broadcastId;
        $this->page = $page;
    }

    public function handle(WhatsAppBroadcastService $broadcastService): void
    {
        $broadcast = WhatsAppBroadcast::with('template')->find($this->broadcastId);

        if (! $broadcast) {
            Log::channel('whatsapp')->warning('⚠️ Broadcast not found', ['broadcast_id' => $this->broadcastId]);
            return;
        }

        $hasMore = $broadcastService->processNextBatch($broadcast, $this->page);

        if ($hasMore) {
            self::dispatch($this->broadcastId, $this->page + 1);
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::channel('whatsapp')->error('❌ ProcessWhatsAppBroadcastJob failed', [
            'broadcast_id' => $this->broadcastId,
            'page' => $this->page,
            'error' => $exception->getMessage(),
        ]);

        $broadcast = WhatsAppBroadcast::find($this->broadcastId);
        if ($broadcast && $broadcast->status === 'processing') {
            $broadcast->update(['status' => 'failed', 'finished_at' => now()]);
        }
    }
}