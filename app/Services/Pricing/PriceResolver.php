<?php

namespace App\Services\Pricing;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Satu-satunya sumber harga yang dipakai bot, halaman pesanan, dan invoice.
 * Harga dasar tetap di product_variants.price; product_variant_prices
 * menyimpan override per kota. Tanpa override, harga dasar berlaku.
 */
class PriceResolver
{
    public function priceFor(ProductVariant $variant, ?int $regionId): int
    {
        $override = $variant->priceForRegion($regionId);

        return (int) ($override?->price ?? $variant->price);
    }

    public function cheapestVariant(?Product $product, ?int $regionId): ?ProductVariant
    {
        if (! $product) {
            return null;
        }

        return $product->variants
            ->where('is_active', true)
            ->sortBy(fn (ProductVariant $variant) => $this->priceFor($variant, $regionId))
            ->first();
    }
}
