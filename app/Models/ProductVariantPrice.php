<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_id',
        'region_id',
        'price',
    ];

    protected $casts = [
        'price' => 'integer',
    ];

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function region()
    {
        return $this->belongsTo(Region::class);
    }
}
