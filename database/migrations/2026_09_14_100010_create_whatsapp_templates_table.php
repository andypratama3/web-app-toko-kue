<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();               // nama template di Meta
            $table->string('meta_template_id')->nullable(); // ID template dari Meta
            $table->string('language', 10)->default('id');
            $table->string('status', 20)->default('APPROVED'); // APPROVED, PENDING, REJECTED, PAUSED
            $table->string('category', 30)->nullable();         // MARKETING, UTILITY, AUTHENTICATION
            $table->text('body_text')->nullable();              // teks body dengan placeholder {{1}} dst.
            $table->string('header_text')->nullable();          // teks header (jika bertype TEXT)
            $table->text('button_text')->nullable();            // label tombol pertama (untuk info)
            $table->json('components')->nullable();             // komponen mentah dari Meta
            $table->unsignedInteger('parameters_count')->default(0); // jumlah placeholder {{n}}
            $table->boolean('is_active')->default(false);       // bisa/dipilih untuk broadcast
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};