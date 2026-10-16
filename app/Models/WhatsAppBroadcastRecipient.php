<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsAppBroadcastRecipient extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcast_recipients';

    protected $fillable = [
        'broadcast_id',
        'customer_id',
        'region_id',
        'phone',
        'name',
        'status',
        'message_id',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
    ];

    public function broadcast(): BelongsTo
    {
        // FK eksplisit: default Laravel ("whats_app_broadcast_id") salah
        // untuk model WhatsAppBroadcast — kolomnya broadcast_id.
        return $this->belongsTo(WhatsAppBroadcast::class, 'broadcast_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }
}