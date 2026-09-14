<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite tidak mendukung drop/modify foreign key; skema awal (2026_09_08_100001)
        // sudah dibuat nullable sehingga cukup identik. Operasi ini khusus MySQL (produksi).
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
        });

        DB::statement('ALTER TABLE whatsapp_messages MODIFY conversation_id CHAR(36) NULL');

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->dropForeign(['conversation_id']);
        });

        DB::statement('ALTER TABLE whatsapp_messages MODIFY conversation_id CHAR(36) NOT NULL');

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            $table->foreign('conversation_id')->references('id')->on('whatsapp_conversations')->cascadeOnDelete();
        });
    }
};