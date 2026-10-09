<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nomor WhatsApp owner per cabang untuk forward format pesanan + bukti bayar.
     * Diisi manual, mis: UPDATE regions SET owner_phone='6281234567890' WHERE slug='surabaya';
     */
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->string('owner_phone')->nullable()->after('meta_phone_number_id');
        });
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn('owner_phone');
        });
    }
};
