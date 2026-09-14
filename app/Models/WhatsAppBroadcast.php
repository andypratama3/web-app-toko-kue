<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppBroadcast extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_broadcasts';

    protected $fillable = [
        'user_id',
        'region_id',
        'whatsapp_template_id',
        'title',
        'body_preview',
        'parameters',
        'recipient_filter',
        'recipient_count',
        'sent_count',
        'failed_count',
        'status',
        'scheduled_at',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'parameters' => 'array',
        'recipient_filter' => 'array',
        'recipient_count' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTemplate::class, 'whatsapp_template_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcastRecipient::class);
    }

    public function pendingRecipients(): HasMany
    {
        return $this->recipients()->where('status', 'pending');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, ['completed', 'failed', 'cancelled']);
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'draft' => 'Draft',
            'queued' => 'Antrian',
            'processing' => 'Diproses',
            'completed' => 'Selesai',
            'failed' => 'Gagal',
            'cancelled' => 'Dibatalkan',
            default => ucfirst($this->status),
        };
    }
}