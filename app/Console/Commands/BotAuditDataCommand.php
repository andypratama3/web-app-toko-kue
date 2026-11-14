<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Region;
use App\Support\ProductImage;
use Illuminate\Console\Command;

/**
 * Audit data yang dipakai bot WhatsApp.
 *
 * Command ini READ-ONLY: tidak mengubah satu baris pun. Tujuannya supaya
 * sebelum/sesudah revisi kita bisa melihat kondisi data nyata yang membuat
 * bot berperilaku aneh di lapangan:
 *
 * - produk tanpa region (dulu bocor ke semua kota),
 * - varian tanpa harga (bot menulis "Harga menyusul"),
 * - produk tanpa foto atau file fotonya hilang di disk,
 * - region tanpa nomor owner (notifikasi order tidak terkirim),
 * - APP_URL masih localhost padahal production (URL foto tidak bisa dibuka).
 *
 * Jalankan di local/testing. Aman juga di production karena tidak menulis.
 */
class BotAuditDataCommand extends Command
{
    protected $signature = 'bot:audit-data';

    protected $description = 'Audit read-only data katalog, foto, dan region yang dipakai bot WhatsApp';

    public function handle(): int
    {
        $this->auditRegions();
        $this->auditProductsPerRegion();
        $this->auditProductsWithoutRegion();
        $this->auditVariantsWithoutPrice();
        $this->auditProductPhotos();
        $this->auditAppUrl();

        $this->newLine();
        $this->info('✅ Audit selesai. Tidak ada data yang diubah.');

        return self::SUCCESS;
    }

    private function auditRegions(): void
    {
        $this->section('REGION');

        $regions = Region::orderBy('name')->get(['id', 'name', 'owner_phone', 'meta_phone_number_id', 'is_active']);

        if ($regions->isEmpty()) {
            $this->warn('  Tidak ada region sama sekali.');

            return;
        }

        $this->table(
            ['ID', 'Nama', 'Aktif', 'owner_phone', 'meta_phone_number_id'],
            $regions->map(fn (Region $region) => [
                $region->id,
                $region->name,
                $region->is_active ? 'ya' : 'tidak',
                $region->owner_phone ?: '— KOSONG',
                $region->meta_phone_number_id ?: '— kosong',
            ])->all()
        );

        $withoutOwner = $regions->filter(fn (Region $region) => blank($region->owner_phone));

        if ($withoutOwner->isNotEmpty()) {
            $this->warn('  ⚠️  Region tanpa owner_phone: '.$withoutOwner->pluck('name')->implode(', '));
        }
    }

    private function auditProductsPerRegion(): void
    {
        $this->section('JUMLAH PRODUK PER REGION');

        $regions = Region::orderBy('name')->get(['id', 'name']);

        $rows = $regions->map(fn (Region $region) => [
            $region->name,
            Product::where('region_id', $region->id)->count(),
            Product::where('region_id', $region->id)->where('is_active', true)->count(),
        ])->all();

        $rows[] = [
            '— global (lintas kota)',
            Product::where('is_global', true)->count(),
            Product::where('is_global', true)->where('is_active', true)->count(),
        ];

        $this->table(['Region', 'Total produk', 'Aktif'], $rows);
    }

    private function auditProductsWithoutRegion(): void
    {
        $this->section('PRODUK GLOBAL & PRODUK TANPA KOTA');

        $global = Product::where('is_global', true)->orderBy('name')->get(['id', 'name', 'is_active']);

        if ($global->isEmpty()) {
            $this->line('  Tidak ada produk global.');
        } else {
            $this->line('  '.$global->count().' produk global (tampil di semua kota):');
            $this->table(
                ['ID', 'Nama', 'Aktif'],
                $global->map(fn (Product $product) => [
                    $product->id,
                    $product->name,
                    $product->is_active ? 'ya' : 'tidak',
                ])->all()
            );
        }

        $orphans = Product::whereNull('region_id')
            ->where('is_global', false)
            ->orderBy('name')
            ->get(['id', 'name', 'is_active']);

        if ($orphans->isEmpty()) {
            $this->line('  Tidak ada produk tanpa kota dan tanpa is_global. Bagus.');

            return;
        }

        $this->warn('  ⚠️  '.$orphans->count().' produk tidak punya region_id dan tidak ditandai is_global — tidak tampil di kota mana pun:');
        $this->table(
            ['ID', 'Nama', 'Aktif'],
            $orphans->map(fn (Product $product) => [
                $product->id,
                $product->name,
                $product->is_active ? 'ya' : 'tidak',
            ])->all()
        );
    }

    private function auditVariantsWithoutPrice(): void
    {
        $this->section('VARIAN TANPA HARGA');

        $variants = ProductVariant::with('product:id,name')
            ->where(fn ($query) => $query->whereNull('price')->orWhere('price', '<=', 0))
            ->orderBy('product_id')
            ->get(['id', 'product_id', 'name', 'price', 'is_active']);

        if ($variants->isEmpty()) {
            $this->line('  Tidak ada. Bagus.');

            return;
        }

        $this->warn('  ⚠️  '.$variants->count().' varian tanpa harga valid (bot menulis "Harga menyusul"):');
        $this->table(
            ['ID', 'Produk', 'Varian', 'Harga', 'Aktif'],
            $variants->map(fn (ProductVariant $variant) => [
                $variant->id,
                $variant->product?->name ?? '—',
                $variant->name,
                $variant->price ?? 'NULL',
                $variant->is_active ? 'ya' : 'tidak',
            ])->all()
        );
    }

    private function auditProductPhotos(): void
    {
        $this->section('FOTO PRODUK');

        $withoutPath = Product::where(fn ($query) => $query->whereNull('image_path')->orWhere('image_path', ''))
            ->orderBy('name')
            ->get(['id', 'name']);

        if ($withoutPath->isEmpty()) {
            $this->line('  Semua produk punya image_path.');
        } else {
            $this->warn('  ⚠️  '.$withoutPath->count().' produk tanpa image_path:');
            $this->table(
                ['ID', 'Nama'],
                $withoutPath->map(fn (Product $product) => [$product->id, $product->name])->all()
            );
        }

        $missingFiles = Product::whereNotNull('image_path')
            ->where('image_path', '!=', '')
            ->orderBy('name')
            ->get(['id', 'name', 'image_path'])
            ->reject(fn (Product $product) => ProductImage::exists($product->image_path));

        if ($missingFiles->isEmpty()) {
            $this->line('  Semua file foto produk ditemukan.');
        } else {
            $this->warn('  ⚠️  '.$missingFiles->count().' produk menunjuk file foto yang TIDAK ADA (bot hanya kirim teks):');
            $this->table(
                ['ID', 'Nama', 'image_path'],
                $missingFiles->map(fn (Product $product) => [
                    $product->id,
                    $product->name,
                    $product->image_path,
                ])->all()
            );
        }
    }

    private function auditAppUrl(): void
    {
        $this->section('APP_URL');

        $appUrl = (string) config('app.url');
        $this->line('  APP_URL = '.($appUrl ?: '— kosong'));

        $isLocalhost = str_contains($appUrl, 'localhost') || str_contains($appUrl, '127.0.0.1');

        if ($isLocalhost && app()->environment('production')) {
            $this->warn('  ⚠️  APP_ENV=production tapi APP_URL masih localhost. URL foto produk tidak akan bisa dibuka di WhatsApp.');
        } elseif ($isLocalhost) {
            $this->line('  (Localhost wajar untuk local/testing.)');
        }
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<options=bold>'.$title.'</>');
        $this->line(str_repeat('─', mb_strlen($title)));
    }
}
