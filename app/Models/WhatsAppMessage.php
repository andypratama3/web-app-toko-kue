<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WhatsAppMessage extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_messages';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'conversation_id',
        'whatsapp_message_id',
        'sender_type',
        'message_type',
        'content',
        'media_url',
        'media_type',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(WhatsAppConversation::class, 'conversation_id');
    }

    public function statuses()
    {
        return $this->hasMany(WhatsAppMessageStatus::class, 'message_id', 'whatsapp_message_id');
    }

    public function scopeIncoming($query)
    {
        return $query->where('sender_type', 'customer');
    }

    public function scopeOutgoing($query)
    {
        return $query->whereIn('sender_type', ['bot', 'admin']);
    }

    public function scopeFromBot($query)
    {
        return $query->where('sender_type', 'bot');
    }
}
