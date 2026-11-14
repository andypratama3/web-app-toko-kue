<?php

namespace App\Models;

use App\Support\ProductImage;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'region_id', // Tambahkan region_id
        'is_global',
        'name',
        'description',
        'image_path',
        'tag',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_global' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Tambahkan relasi ke Region
    public function region()
    {
        return $this->belongsTo(Region::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    /**
     * Produk yang tampil di kota tertentu: produk kota itu + produk global.
     * Produk ber-region lain tidak pernah ikut, termasuk yang region_id NULL
     * tapi tidak ditandai is_global.
     */
    public function scopeVisibleInRegion($query, ?int $regionId)
    {
        return $query->where(function ($q) use ($regionId) {
            $q->where('is_global', true);
            if ($regionId !== null) {
                $q->orWhere('region_id', $regionId);
            }
        });
    }

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): string => ProductImage::url($this->image_path),
        );
    }
}
