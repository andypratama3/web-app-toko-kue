<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Produk lintas kota sekarang ditandai eksplisit lewat is_global.
     * Backfill: semua produk lama dengan region_id NULL dianggap global
     * supaya tidak ada produk yang hilang dari katalog setelah
     * orWhereNull('region_id') dihapus dari query.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_global')->default(false)->after('region_id');
            $table->index('is_global');
        });

        DB::table('products')->whereNull('region_id')->update(['is_global' => true]);
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['is_global']);
            $table->dropColumn('is_global');
        });
    }
};
