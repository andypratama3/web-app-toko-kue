<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class WhatsAppConversation extends Model
{
    use HasUuids;

    protected $table = 'whatsapp_conversations';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'phone_number',
        'profile_name',
        'customer_id',
        'region_id',
        'status',
        'current_state',
        'context',
        'message_count',
        'last_message_at',
    ];

    protected $casts = [
        'context' => 'array',
        'message_count' => 'integer',
        'last_message_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'conversation_id');
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(WhatsAppMessage::class, 'conversation_id')->latestOfMany();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function getContext(string $key, $default = null)
    {
        return data_get($this->context, $key, $default);
    }

    public function setContext(string $key, $value): void
    {
        $context = $this->context ?? [];
        data_set($context, $key, $value);
        $this->update(['context' => $context]);
    }

    public function clearContext(): void
    {
        $this->update(['context' => null]);
    }

    public function incrementMessageCount(): void
    {
        $this->increment('message_count');
        $this->update(['last_message_at' => now()]);
    }
}
