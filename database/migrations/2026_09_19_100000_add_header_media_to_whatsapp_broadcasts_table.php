<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            // image | video | document — menyesuaikan tipe header template di Meta
            $table->string('header_media_type', 20)->nullable()->after('body_preview');
            // URL publik media (hasil upload ke storage/public/broadcast_media atau URL eksternal)
            $table->text('header_media_url')->nullable()->after('header_media_type');
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_broadcasts', function (Blueprint $table) {
            $table->dropColumn(['header_media_type', 'header_media_url']);
        });
    }
};