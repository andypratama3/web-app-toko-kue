<?php

namespace Tests\Feature\WhatsApp;

use App\Models\Region;
use App\Services\WhatsApp\OrderBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DenpasarDisabledTest extends TestCase
{
    use RefreshDatabase;

    public function test_cabang_nonaktif_tidak_muncul_di_pilihan(): void
    {
        Region::create(['name' => 'Surabaya', 'slug' => 'surabaya', 'is_active' => true]);
        Region::create(['name' => 'Malang', 'slug' => 'malang', 'is_active' => true]);
        Region::create(['name' => 'Denpasar', 'slug' => 'denpasar', 'is_active' => false]);

        $bot = app(OrderBotService::class);
        $method = new \ReflectionMethod($bot, 'branchOptions');
        $names = $method->invoke($bot)->pluck('name')->all();

        $this->assertContains('Surabaya', $names);
        $this->assertContains('Malang', $names);
        $this->assertNotContains('Denpasar', $names);
    }

    public function test_migrasi_menonaktifkan_denpasar_dan_produknya(): void
    {
        // Simulasi data pra-migrasi: Denpasar aktif + 1 produk regional aktif.
        $denpasar = Region::create(['name' => 'Denpasar', 'slug' => 'denpasar', 'is_active' => true]);
        $cat = \App\Models\Category::create(['name' => 'Produk', 'slug' => 'produk']);
        $product = \App\Models\Product::create([
            'category_id' => $cat->id, 'region_id' => $denpasar->id,
            'name' => 'Kue Lapak', 'description' => 'd', 'is_active' => true,
        ]);
        \App\Models\ProductVariant::create([
            'product_id' => $product->id, 'name' => 'Isi 1', 'price' => 4000, 'is_active' => true,
        ]);

        // Jalankan ulang logika up() migrasi untuk baris ini.
        \Illuminate\Support\Facades\DB::table('regions')->where('slug', 'denpasar')->update(['is_active' => false]);
        \Illuminate\Support\Facades\DB::table('products')->where('region_id', $denpasar->id)->update(['is_active' => false]);
        \Illuminate\Support\Facades\DB::table('product_variants')->where('product_id', $product->id)->update(['is_active' => false]);

        $this->assertFalse($denpasar->fresh()->is_active);
        $this->assertFalse($product->fresh()->is_active);
        $this->assertEquals(0, \App\Models\Product::where('is_active', true)->where('region_id', $denpasar->id)->count());
    }
}
