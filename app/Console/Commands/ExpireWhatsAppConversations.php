<?php

namespace App\Console\Commands;

use App\Enums\OrderBotConversationState;
use App\Models\WhatsAppConversation;
use Illuminate\Console\Command;

class ExpireWhatsAppConversations extends Command
{
    protected $signature = 'whatsapp:expire-conversations
                            {--hours=24 : Minimal umur tanpa aktivitas (jam) sebelum percakapan direset}';

    protected $description = 'Reset percakapan WhatsApp yang terbengkalai agar bot tidak terjebak di satu sesi';

    public function handle(): int
    {
        $hours = max(1, (int) $this->option('hours'));
        $threshold = now()->subHours($hours);

        $intermediateStates = [
            OrderBotConversationState::WELCOME_SENT->value,
            OrderBotConversationState::MENU_SELECTION->value,
            OrderBotConversationState::PRODUCT_BROWSING->value,
            OrderBotConversationState::AWAITING_ORDER_FORM->value,
            OrderBotConversationState::AWAITING_DELIVERY_METHOD->value,
            OrderBotConversationState::AWAITING_LOCATION_OR_ADDRESS->value,
            OrderBotConversationState::AWAITING_DELIVERY_SLOT->value,
            OrderBotConversationState::ORDER_SUMMARY->value,
            OrderBotConversationState::AWAITING_PAYMENT_PROOF->value,
            OrderBotConversationState::ORDER_CONFIRMED->value,
        ];

        $expired = WhatsAppConversation::whereIn('current_state', $intermediateStates)
            ->where('status', 'active')
            ->where(function ($q) use ($threshold) {
                $q->whereNull('last_message_at')->orWhere('last_message_at', '<', $threshold);
            })
            ->get();

        $count = 0;
        foreach ($expired as $conversation) {
            $conversation->update([
                'current_state' => OrderBotConversationState::WELCOME_SENT->value,
                'context' => null,
            ]);
            $count++;
        }

        $this->info("✓ {$count} percakapan terbengkalai berhasil direset.");

        return self::SUCCESS;
    }
}