<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageStatus extends Model
{
    protected $table = 'whatsapp_message_statuses';

    protected $fillable = [
        'message_id',
        'status',
        'recipient',
        'timestamp',
        'errors',
    ];

    protected $casts = [
        'errors' => 'array',
        'timestamp' => 'datetime',
    ];

    public function message()
    {
        return $this->belongsTo(WhatsAppMessage::class, 'message_id', 'whatsapp_message_id');
    }
}
