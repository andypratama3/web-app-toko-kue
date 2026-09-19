<?php

namespace App\Console\Commands;

use App\Services\WhatsApp\WhatsAppBroadcastService;
use Illuminate\Console\Command;

class SyncWhatsAppTemplates extends Command
{
    protected $signature = 'broadcast:sync-templates';

    protected $description = 'Sinkronkan template WhatsApp dari Meta ke tabel lokal secara langsung (tanpa queue).';

    public function handle(WhatsAppBroadcastService $service): int
    {
        $this->info('🔄 Menarik template APPROVED dari Meta...');

        $saved = $service->syncTemplates();

        $this->info("✅ Selesai. {$saved} template disimpan/diperbarui.");

        return self::SUCCESS;
    }
}