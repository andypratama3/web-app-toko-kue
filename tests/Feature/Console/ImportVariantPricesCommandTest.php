<?php

namespace Tests\Feature\Console;

use App\Models\ProductVariantPrice;
use App\Models\Region;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\BuildsBotWorld;
use Tests\TestCase;

class ImportVariantPricesCommandTest extends TestCase
{
    use BuildsBotWorld;
    use RefreshDatabase;

    protected Region $surabaya;

    protected Region $malang;

    /** @var array<int, string> */
    protected array $tempFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->surabaya = $this->makeRegion('Surabaya');
        $this->malang = $this->makeRegion('Malang');

        $category = $this->makeCategory('Kue Tradisional');
        $this->makeProduct($category, 'Kue Pandan Asli', [
            ['name' => 'Reguler', 'price' => 12000],
            ['name' => 'Besar', 'price' => 20000],
        ]);
    }

    protected function tearDown(): void
    {
        foreach ($this->tempFiles as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    protected function csv(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), 'harga_');
        file_put_contents($path, $contents);
        $this->tempFiles[] = $path;

        return $path;
    }

    public function test_dry_run_tidak_menulis_dan_melaporkan_aksi(): void
    {
        $variant = \App\Models\Product::where('name', 'Kue Pandan Asli')->firstOrFail()
            ->variants()->where('name', 'Reguler')->firstOrFail();

        ProductVariantPrice::create([
            'product_variant_id' => $variant->id,
            'region_id' => $this->surabaya->id,
            'price' => 15000,
        ]);

        $path = $this->csv(
            "region,product,variant,price\n".
            "Surabaya,Kue Pandan Asli,Reguler,20000\n".
            "Malang,Kue Pandan Asli,Reguler,18000\n".
            "Surabaya,Kue Pandan Asli,Besar,Rp 25.000\n"
        );

        $this->artisan('bot:import-prices', ['file' => $path])
            ->expectsOutputToContain('DRY-RUN')
            ->expectsOutputToContain('perbarui')
            ->assertExitCode(0);

        $this->assertSame(1, ProductVariantPrice::count());
        $this->assertSame(15000, ProductVariantPrice::firstOrFail()->price);
    }

    public function test_apply_menulis_harga_baru_dan_memperbarui_yang_ada(): void
    {
        $product = \App\Models\Product::where('name', 'Kue Pandan Asli')->firstOrFail();
        $reguler = $product->variants()->where('name', 'Reguler')->firstOrFail();
        $besar = $product->variants()->where('name', 'Besar')->firstOrFail();

        ProductVariantPrice::create([
            'product_variant_id' => $reguler->id,
            'region_id' => $this->surabaya->id,
            'price' => 15000,
        ]);

        $path = $this->csv(
            "region,product,variant,price\n".
            "Surabaya,Kue Pandan Asli,Reguler,20000\n".
            "Malang,Kue Pandan Asli,Reguler,18000\n".
            "Surabaya,Kue Pandan Asli,Besar,Rp 25.000\n"
        );

        $this->artisan('bot:import-prices', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('3 baris')
            ->assertExitCode(0);

        $this->assertSame(3, ProductVariantPrice::count());
        $this->assertDatabaseHas('product_variant_prices', [
            'product_variant_id' => $reguler->id,
            'region_id' => $this->surabaya->id,
            'price' => 20000,
        ]);
        $this->assertDatabaseHas('product_variant_prices', [
            'product_variant_id' => $reguler->id,
            'region_id' => $this->malang->id,
            'price' => 18000,
        ]);
        $this->assertDatabaseHas('product_variant_prices', [
            'product_variant_id' => $besar->id,
            'region_id' => $this->surabaya->id,
            'price' => 25000,
        ]);
    }

    public function test_baris_tidak_valid_menggagalkan_apply_tanpa_menulis(): void
    {
        $path = $this->csv(
            "region,product,variant,price\n".
            "Surabaya,Kue Pandan Asli,Reguler,20000\n".
            "Denpasar,Kue Pandan Asli,Reguler,18000\n"
        );

        $this->artisan('bot:import-prices', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('Denpasar')
            ->assertExitCode(1);

        $this->assertSame(0, ProductVariantPrice::count());
    }

    public function test_header_wajib_lengkap(): void
    {
        $path = $this->csv("region,product,price\nSurabaya,Kue Pandan Asli,20000\n");

        $this->artisan('bot:import-prices', ['file' => $path])
            ->expectsOutputToContain('variant')
            ->assertExitCode(1);

        $this->assertSame(0, ProductVariantPrice::count());
    }

    public function test_baris_duplikat_ditolak(): void
    {
        $path = $this->csv(
            "region,product,variant,price\n".
            "Surabaya,Kue Pandan Asli,Reguler,20000\n".
            "Surabaya,Kue Pandan Asli,Reguler,21000\n"
        );

        $this->artisan('bot:import-prices', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('duplikat')
            ->assertExitCode(1);

        $this->assertSame(0, ProductVariantPrice::count());
    }

    public function test_produk_ambigu_dilaporkan(): void
    {
        $category = $this->makeCategory('Kue Lain');
        $this->makeProduct($category, 'Kue Pandan Asli', [
            ['name' => 'Reguler', 'price' => 13000],
        ]);

        $path = $this->csv(
            "region,product,variant,price\n".
            "Surabaya,Kue Pandan Asli,Reguler,20000\n"
        );

        $this->artisan('bot:import-prices', ['file' => $path, '--apply' => true])
            ->expectsOutputToContain('ambigu')
            ->assertExitCode(1);

        $this->assertSame(0, ProductVariantPrice::count());
    }
}
