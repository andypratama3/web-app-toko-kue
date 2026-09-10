<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryZone extends Model
{
    protected $fillable = [
        'region_id',
        'area_name',
        'landmark_keyword',
        'distance_km',
        'ongkir',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'distance_km' => 'decimal:2',
        'ongkir' => 'integer',
        'is_active' => 'boolean',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRegion($query, int $regionId)
    {
        return $query->where('region_id', $regionId);
    }
}
