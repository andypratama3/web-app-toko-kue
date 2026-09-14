<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // admin pembuat
            $table->foreignId('region_id')->nullable()->constrained()->nullOnDelete(); // null = semua cabang
            $table->foreignId('whatsapp_template_id')->constrained()->cascadeOnDelete();
            $table->string('title')->nullable();
            $table->text('body_preview')->nullable();       // contoh hasil render setelah parameter diisi
            $table->json('parameters')->nullable();         // [1 => nilai, 2 => nilai, ...] (nilai bisa token {nama})
            $table->json('recipient_filter')->nullable();   // filter pemilih penerima (category, hanya opt-in, dll.)
            $table->unsignedInteger('recipient_count')->default(0);
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->string('status', 20)->default('draft'); // draft, queued, processing, completed, failed, cancelled
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_broadcasts');
    }
};