<?php

namespace App\Console\Commands;

use Database\Seeders\DemoResponsiveSeeder;
use Illuminate\Console\Command;

/**
 * Pembungkus DemoResponsiveSeeder dengan opsi --reverse.
 *
 * `db:seed` menolak opsi yang tidak dikenal ("The --reverse option does not
 * exist") sebelum seeder sempat jalan, jadi opsi reverse harus datang dari
 * command yang memang mendeklarasikannya — yaitu command ini.
 *
 * Pakai:
 *   php artisan demo:seed            -> isi data demo
 *   php artisan demo:seed --reverse  -> hapus data demo yang dibuatnya
 */
class DemoSeedCommand extends Command
{
    protected $signature = 'demo:seed
                            {--reverse : Hapus data demo yang sebelumnya dibuat oleh seeder ini}';

    protected $description = 'Isi (atau bersihkan) data demo untuk mengecek tampilan responsif admin.';

    public function handle(): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Ditolak: data demo hanya boleh jalan di local/testing.');

            return self::FAILURE;
        }

        $this->info($this->option('reverse')
            ? 'Menghapus data demo...'
            : 'Membuat data demo...');

        $seeder = new DemoResponsiveSeeder();
        $seeder->setContainer(app())->setCommand($this);
        $seeder->__invoke();

        return self::SUCCESS;
    }
}
