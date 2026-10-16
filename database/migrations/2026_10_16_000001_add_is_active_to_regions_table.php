<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Nonaktifkan cabang Denpasar di seluruh aplikasi tanpa menghapus data
     * (produk, order, customer, user Denpasar tetap utuh untuk riwayat).
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('slug');
        });

        DB::table('regions')->where('slug', 'denpasar')->update(['is_active' => false]);

        // Produk khusus Denpasar disembunyikan dari katalog (data tetap ada).
        DB::table('products')
            ->whereIn('category_id', function ($q) {
                $q->select('id')->from('categories');
            })
            ->where('region_id', function ($q) {
                $q->select('id')->from('regions')->where('slug', 'denpasar')->limit(1);
            })
            ->update(['is_active' => false]);

        DB::table('product_variants')
            ->whereIn('product_id', function ($q) {
                $q->select('p.id')->from('products as p')
                    ->join('regions as r', 'r.id', '=', 'p.region_id')
                    ->where('r.slug', 'denpasar');
            })
            ->update(['is_active' => false]);
    }

    public function down(): void
    {
        DB::table('regions')->where('slug', 'denpasar')->update(['is_active' => true]);

        DB::table('products')
            ->where('region_id', function ($q) {
                $q->select('id')->from('regions')->where('slug', 'denpasar')->limit(1);
            })
            ->update(['is_active' => true]);

        DB::table('product_variants')
            ->whereIn('product_id', function ($q) {
                $q->select('p.id')->from('products as p')
                    ->join('regions as r', 'r.id', '=', 'p.region_id')
                    ->where('r.slug', 'denpasar');
            })
            ->update(['is_active' => true]);

        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
