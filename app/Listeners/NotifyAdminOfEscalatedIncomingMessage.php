<?php

namespace App\Listeners;

use App\Enums\OrderBotConversationState;
use App\Events\WhatsAppMessageReceived;
use App\Models\AdminNotification;
use App\Models\User;
use App\Models\WhatsAppConversation;

class NotifyAdminOfEscalatedIncomingMessage
{
    public function handle(WhatsAppMessageReceived $event): void
    {
        if ($event->message->sender_type !== 'customer') {
            return;
        }

        $conversation = $event->message->conversation()->first();

        if (!$conversation || $conversation->current_state !== OrderBotConversationState::ESCALATED_TO_HUMAN->value) {
            return;
        }

        $admins = User::whereHas('roles', fn($q) => $q->where('name', 'admin'))
            ->where('region_id', $conversation->region_id)
            ->get();

        foreach ($admins as $admin) {
            AdminNotification::create([
                'user_id' => $admin->id,
                'region_id' => $conversation->region_id,
                'type' => 'message',
                'title' => 'Pesan masuk (perlu respon)',
                'message' => "Dari {$conversation->profile_name}: " . mb_substr((string)$event->content, 0, 200),
            ]);
        }
    }
}