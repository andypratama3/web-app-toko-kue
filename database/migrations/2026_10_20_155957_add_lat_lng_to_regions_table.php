<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 7)->nullable()->after('slug');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
        });

        // Backfill existing known regions with coordinates from old hardcode
        DB::table('regions')->where('slug', 'surabaya')->update(['latitude' => -7.2575, 'longitude' => 112.7521]);
        DB::table('regions')->where('slug', 'malang')->update(['latitude' => -7.9666, 'longitude' => 112.6326]);
        DB::table('regions')->where('slug', 'denpasar')->update(['latitude' => -8.6500, 'longitude' => 115.2167]);
    }

    public function down(): void
    {
        Schema::table('regions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
        });
    }
};
