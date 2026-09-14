<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsAppTemplate extends Model
{
    use HasFactory;

    protected $table = 'whatsapp_templates';

    protected $fillable = [
        'name',
        'meta_template_id',
        'language',
        'status',
        'category',
        'body_text',
        'header_text',
        'button_text',
        'components',
        'parameters_count',
        'is_active',
        'last_synced_at',
    ];

    protected $casts = [
        'components' => 'array',
        'parameters_count' => 'integer',
        'is_active' => 'boolean',
        'last_synced_at' => 'datetime',
    ];

    public function broadcasts(): HasMany
    {
        return $this->hasMany(WhatsAppBroadcast::class, 'whatsapp_template_id');
    }

    /**
     * Parameter yang tersedia (contoh NFC: 1,2,3 sesuai placeholder {{1}} dst).
     */
    public function parameterKeys(): array
    {
        $total = max(1, $this->parameters_count);
        return range(1, $total);
    }
}