<?php

namespace Tests\Feature\Pricing;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Region;
use App\Services\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PriceResolverTest extends TestCase
{
    use RefreshDatabase;

    protected PriceResolver $resolver;

    protected Region $surabaya;

    protected Region $malang;

    protected ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(PriceResolver::class);
        $this->surabaya = $this->makeRegion('Surabaya');
        $this->malang = $this->makeRegion('Malang');

        $category = Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $product = Product::create([
            'category_id' => $category->id,
            'is_global' => true,
            'name' => 'Kue Ijo',
            'description' => 'ijo',
            'is_active' => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Isi 3',
            'price' => 10000,
            'is_active' => true,
        ]);
    }

    protected function makeRegion(string $name): Region
    {
        return Region::create([
            'name' => $name,
            'slug' => Str::slug($name),
            'is_active' => true,
        ]);
    }

    public function test_tanpa_override_memakai_harga_dasar(): void
    {
        $this->assertSame(10000, $this->resolver->priceFor($this->variant, $this->surabaya->id));
    }

    public function test_override_kota_dipakai_dan_kota_lain_tetap_harga_dasar(): void
    {
        ProductVariantPrice::create([
            'product_variant_id' => $this->variant->id,
            'region_id' => $this->surabaya->id,
            'price' => 12500,
        ]);

        $this->assertSame(12500, $this->resolver->priceFor($this->variant->fresh(), $this->surabaya->id));
        $this->assertSame(10000, $this->resolver->priceFor($this->variant->fresh(), $this->malang->id));
        $this->assertSame(10000, $this->resolver->priceFor($this->variant->fresh(), null));
    }

    public function test_override_ikut_terbaca_saat_relasi_prices_sudah_dimuat(): void
    {
        ProductVariantPrice::create([
            'product_variant_id' => $this->variant->id,
            'region_id' => $this->malang->id,
            'price' => 9000,
        ]);

        $loaded = ProductVariant::with(['prices' => fn ($q) => $q->where('region_id', $this->malang->id)])
            ->find($this->variant->id);

        $this->assertTrue($loaded->relationLoaded('prices'));
        $this->assertSame(9000, $this->resolver->priceFor($loaded, $this->malang->id));
        $this->assertSame(10000, $this->resolver->priceFor($loaded, $this->surabaya->id));
    }

    public function test_varian_termurah_dihitung_dari_harga_kota(): void
    {
        $kedua = ProductVariant::create([
            'product_id' => $this->variant->product_id,
            'name' => 'Isi 5',
            'price' => 15000,
            'is_active' => true,
        ]);
        ProductVariantPrice::create([
            'product_variant_id' => $kedua->id,
            'region_id' => $this->surabaya->id,
            'price' => 9000,
        ]);

        $product = Product::with(['variants' => fn ($q) => $q->where('is_active', true)])
            ->find($this->variant->product_id);

        $this->assertSame('Isi 3', $this->resolver->cheapestVariant($product, $this->malang->id)->name);
        $this->assertSame('Isi 5', $this->resolver->cheapestVariant($product, $this->surabaya->id)->name);
    }
}
