<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Region;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Import harga varian per kota dari CSV.
 *
 * Default DRY-RUN: seluruh baris divalidasi dan dilaporkan, tetapi tidak ada
 * yang ditulis. Gunakan --apply hanya setelah hasil dry-run diperiksa dan
 * database sudah dibackup.
 */
class ImportVariantPricesCommand extends Command
{
    protected $signature = 'bot:import-prices
        {file : Path file CSV}
        {--apply : Tulis perubahan ke database (default dry-run)}';

    protected $description = 'Validasi/import harga varian per kota dari CSV (default dry-run)';

    public function handle(): int
    {
        $path = (string) $this->argument('file');

        if (! is_file($path) || ! is_readable($path)) {
            $this->error("File tidak ditemukan atau tidak bisa dibaca: {$path}");

            return self::FAILURE;
        }

        if (! Schema::hasTable('product_variant_prices')) {
            $this->error('Tabel product_variant_prices belum ada. Jalankan migrasi terlebih dahulu.');

            return self::FAILURE;
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            $this->error("Gagal membuka file: {$path}");

            return self::FAILURE;
        }

        $header = fgetcsv($handle, null, ',', '"', '');
        if ($header === false) {
            fclose($handle);
            $this->error('File CSV kosong.');

            return self::FAILURE;
        }

        $columns = array_map(
            fn ($column) => strtolower(trim((string) $column)),
            $header
        );
        $columns[0] = preg_replace('/^\xEF\xBB\xBF/', '', $columns[0]);

        $required = ['region', 'product', 'variant', 'price'];
        $missing = array_values(array_diff($required, $columns));

        if ($missing !== []) {
            fclose($handle);
            $this->error('Header wajib: region, product, variant, price. Kolom hilang: '.implode(', ', $missing).'.');

            return self::FAILURE;
        }

        $index = array_flip($columns);
        $regions = Region::all();
        $operations = [];
        $errors = [];
        $seen = [];
        $line = 1;

        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            $line++;

            if ($this->isEmptyRow($row)) {
                continue;
            }

            $regionName = trim((string) ($row[$index['region']] ?? ''));
            $productName = trim((string) ($row[$index['product']] ?? ''));
            $variantName = trim((string) ($row[$index['variant']] ?? ''));
            $rawPrice = trim((string) ($row[$index['price']] ?? ''));

            $region = $regions->first(fn (Region $candidate) => mb_strtolower($candidate->name) === mb_strtolower($regionName)
                || mb_strtolower((string) $candidate->slug) === mb_strtolower($regionName));

            if (! $region) {
                $errors[] = "Baris {$line}: kota *{$regionName}* tidak ditemukan.";

                continue;
            }

            $products = Product::with('variants')
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($productName)])
                ->where(fn ($query) => $query
                    ->where('region_id', $region->id)
                    ->orWhere('is_global', true))
                ->get();

            if ($products->isEmpty()) {
                $errors[] = "Baris {$line}: produk *{$productName}* tidak ditemukan untuk kota {$region->name}.";

                continue;
            }

            if ($products->count() > 1) {
                $errors[] = "Baris {$line}: produk *{$productName}* ambigu untuk kota {$region->name} ({$products->count()} kandidat).";

                continue;
            }

            $product = $products->first();
            $variant = $product->variants->first(
                fn (ProductVariant $candidate) => mb_strtolower($candidate->name) === mb_strtolower($variantName)
            );

            if (! $variant) {
                $errors[] = "Baris {$line}: varian *{$variantName}* tidak ditemukan pada produk {$product->name}.";

                continue;
            }

            $price = $this->parsePrice($rawPrice);
            if ($price === null) {
                $errors[] = "Baris {$line}: harga *{$rawPrice}* tidak valid.";

                continue;
            }

            $key = $variant->id.':'.$region->id;
            if (isset($seen[$key])) {
                $errors[] = "Baris {$line}: duplikat {$product->name} / {$variant->name} / {$region->name} (sudah ada di baris {$seen[$key]}).";

                continue;
            }
            $seen[$key] = $line;

            $existing = ProductVariantPrice::where('product_variant_id', $variant->id)
                ->where('region_id', $region->id)
                ->first();

            $operations[] = [
                'line' => $line,
                'region' => $region,
                'product' => $product,
                'variant' => $variant,
                'price' => $price,
                'action' => ! $existing ? 'buat' : ((int) $existing->price === $price ? 'tetap' : 'perbarui'),
            ];
        }

        fclose($handle);

        if ($errors !== []) {
            foreach ($errors as $error) {
                $this->error($error);
            }

            $this->warn('Tidak ada perubahan ditulis.');

            return self::FAILURE;
        }

        if ($operations === []) {
            $this->warn('Tidak ada baris data.');

            return self::SUCCESS;
        }

        $this->table(
            ['Baris', 'Kota', 'Produk', 'Varian', 'Harga', 'Aksi'],
            array_map(fn (array $operation) => [
                $operation['line'],
                $operation['region']->name,
                $operation['product']->name,
                $operation['variant']->name,
                'Rp '.number_format($operation['price'], 0, ',', '.'),
                $operation['action'],
            ], $operations)
        );

        $counts = array_count_values(array_column($operations, 'action'));
        $summary = sprintf(
            'Ringkasan: %d buat, %d perbarui, %d tetap, 0 gagal.',
            $counts['buat'] ?? 0,
            $counts['perbarui'] ?? 0,
            $counts['tetap'] ?? 0
        );

        if (! $this->option('apply')) {
            $this->warn($summary);
            $this->warn('DRY-RUN: tidak ada perubahan ditulis. Tambahkan --apply setelah hasil ini diperiksa.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($operations) {
            foreach ($operations as $operation) {
                ProductVariantPrice::updateOrCreate(
                    [
                        'product_variant_id' => $operation['variant']->id,
                        'region_id' => $operation['region']->id,
                    ],
                    ['price' => $operation['price']]
                );
            }
        });

        $this->info($summary);
        $this->info('✅ '.count($operations).' baris diproses.');

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parsePrice(string $raw): ?int
    {
        if ($raw === '' || preg_match('/-\s*\d/', $raw)) {
            return null;
        }

        $digits = preg_replace('/[^\d]/', '', $raw);
        if ($digits === '' || $digits === null) {
            return null;
        }

        $price = (int) $digits;

        return $price > 0 ? $price : null;
    }
}
