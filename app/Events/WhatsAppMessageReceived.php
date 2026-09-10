<?php

namespace App\Events;

use App\Models\WhatsAppMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WhatsAppMessageReceived
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public WhatsAppMessage $message;
    public string $phone;
    public string $content;

    public function __construct(WhatsAppMessage $message, string $phone, string $content)
    {
        $this->message = $message;
        $this->phone = $phone;
        $this->content = $content;
    }
}
